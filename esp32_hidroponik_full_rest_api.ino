#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <WiFiManager.h>      // Library: "WiFiManager" by tzapu
#include <Preferences.h>      // NVS Flash storage ESP32
#include <Wire.h>
#include <RTClib.h>           // Library: "RTClib" by Adafruit
#include <hd44780.h>          // Library: "hd44780" by Bill Perry
#include <hd44780ioClass/hd44780_I2Cexp.h>
#include <DHT.h>              // Library: "DHT sensor library" by Adafruit
#include <OneWire.h>          // Library: "OneWire"
#include <DallasTemperature.h>// Library: "DallasTemperature"
#include <NTPClient.h>        // Library: "NTPClient" by Fabrice Weinberg
#include <WiFiUdp.h>
#include <ArduinoJson.h>       // Library: "ArduinoJson" v6 or v7

// ==============================================================================
// 1. PIN DEFINITIONS & HARDWARE CONFIGURATION
// ==============================================================================
#define SSR_SOLENOID          25    // Relay Solenoid Valve Pengisian Air
#define BUZZER_PIN            26    // Buzzer Alarm / Peringatan
#define WATER_SWITCH_LOW_PIN  27    // Float Switch Bawah (INPUT_PULLUP)
#define WATER_SWITCH_HIGH_PIN 14    // Float Switch Atas (INPUT_PULLUP)

#define DHTPIN                4     // Sensor Suhu & Kelembaban Udara (DHT22)
#define DHTTYPE               DHT22
#define DHT_TEMP_OFFSET       1.4f 
#define DHT_HUM_OFFSET       -18.4f

#define ONE_WIRE_BUS          18    // Sensor Suhu Air (DS18B20 OneWire)
#define WATER_TEMP_OFFSET     0.4f

#define SDA_PIN               21    // I2C 1 (LCD 20x4) SDA
#define SCL_PIN               22    // I2C 1 (LCD 20x4) SCL

#define SDA2_PIN              16    // I2C 2 (RTC DS3231) SDA (Pin RX2)
#define SCL2_PIN              17    // I2C 2 (RTC DS3231) SCL (Pin TX2)

#define PH_PIN                33    // Sensor pH (ADC1)
#define TDS_PIN               34    // Sensor TDS (ADC1)

// ==============================================================================
// 2. SENSOR CALIBRATION PARAMETERS
// ==============================================================================
#define PH_CALIBRATION_OFFSET   20.5910f
#define PH_CALIBRATION_SLOPE   -5.4006f
#define TDS_CALIBRATION_FACTOR  1.27f

// ==============================================================================
// 3. OBJECT INSTANTIATION (GLOBAL TO PREVENT STACK OVERFLOW)
// ==============================================================================
hd44780_I2Cexp     lcd;
bool               lcdAvailable = false;
TwoWire            I2C_2 = TwoWire(1);
RTC_DS3231         rtc;
bool               rtcAvailable = false;
DHT                dht(DHTPIN, DHTTYPE);
OneWire            oneWire(ONE_WIRE_BUS);
DallasTemperature  ds18b20(&oneWire);
int                ds18b20Count = 0;
Preferences        preferences;
WiFiUDP            ntpUDP;
NTPClient          timeClient(ntpUDP, "pool.ntp.org", 28800); // UTC+8 WITA
WiFiClientSecure   clientSecure; // Global client to save stack memory

// ==============================================================================
// 4. SERVER & REST API CREDENTIALS
// ==============================================================================
char website_base_url[128] = "https://harvesthouse.biz.id";
char website_api_key[32]   = "HARVEST123";

// ==============================================================================
// 5. GLOBAL STATE & FILTER VARIABLES
// ==============================================================================
float waterTemp  = 25.0f;
float airTemp    = 28.0f;
float humidity   = 65.0f;
float phValue    = 6.5f;
float tdsValue   = 500.0f;

bool  ssrActive  = false;
bool  g_lowSwitch  = false;
bool  g_highSwitch = false;

// Kalman & Moving Average Filter untuk TDS
#define MA_WINDOW 5
float tds_history[MA_WINDOW] = {500.0f, 500.0f, 500.0f, 500.0f, 500.0f};
int   ma_index = 0;
float tds_kalman_estimate       = 500.0f;
float tds_kalman_error_estimate = 1.0f;
float tds_kalman_error_measure  = 15.0f;
float tds_kalman_q              = 0.1f;

#define KALMAN_RESET_INTERVAL   300000UL
unsigned long lastKalmanResetTime = 0;

// Warmup State (Hanya 3 detik)
#define WARMUP_DURATION         3000UL
bool          warmup_complete   = false;
unsigned long warmup_start_time = 0;

// Safety & Emergency
#define MAX_FILLING_RUNTIME       5400000UL // 90 menit max
#define SAFETY_CHECK_INTERVAL     3000UL
#define EMERGENCY_COOLDOWN_MS     5000UL
#define MAX_EMERGENCY_CYCLES      3

bool          emergencyStop        = false;
unsigned long emergencyStartTime   = 0;
unsigned long lastSafetyCheck      = 0;
unsigned int  emergencyCycleCount  = 0;
bool          systemLockout        = false;
bool          switchLogicError     = false;
unsigned long errorStartTime       = 0;

unsigned long bothLowStartMs       = 0;
unsigned long fillingStartMs       = 0;

enum WaterControlState {
    STATE_IDLE,
    STATE_FILLING,
    STATE_FULL,
    STATE_BETWEEN,
    STATE_EMERGENCY
};
WaterControlState waterControlState = STATE_IDLE;

// Timing intervals
const unsigned long SENSOR_READ_INTERVAL = 3000UL;  // Baca sensor tiap 3 detik
const unsigned long REST_POST_INTERVAL   = 5000UL;  // Kirim HTTP POST tiap 5 detik
const unsigned long LCD_UPDATE_INTERVAL  = 3000UL;  // Update LCD tiap 3 detik

unsigned long previousSensorRead = 0;
unsigned long previousRestPost   = 0;
unsigned long previousLCDUpdate  = 0;

// Anti-spam Cooldown
unsigned long cooldownWaterLevel  = 0;
unsigned long cooldownSolenoid    = 0;
unsigned long cooldownEmergency   = 0;

#define CD_WATER_LEVEL    300000UL  // 5 menit
#define CD_SOLENOID       60000UL   // 1 menit
#define CD_EMERGENCY      900000UL  // 15 menit

String lastNotifWaterLevel = "";
bool   lastNotifSolenoid   = false;

// ==============================================================================
// 6. BUZZER CONTROL FUNCTIONS
// ==============================================================================
void setupBuzzer() {
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepClick() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(40);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepSuccess() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(80);
    digitalWrite(BUZZER_PIN, LOW);
    delay(60);
    digitalWrite(BUZZER_PIN, HIGH);
    delay(120);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepAlarm() {
    for (int i = 0; i < 3; i++) {
        digitalWrite(BUZZER_PIN, HIGH);
        delay(100);
        digitalWrite(BUZZER_PIN, LOW);
        delay(70);
    }
}

// ==============================================================================
// 7. WIFIMANAGER HOTSPOT PORTAL CALLBACK
// ==============================================================================
void configModeCallback(WiFiManager *myWiFiManager) {
    Serial.println("\n[WIFI MANAGER] Masuk Mode Access Point (Hotspot Setup)");
    Serial.print("[WIFI MANAGER] Hubungkan HP ke SSID: ");
    Serial.println(myWiFiManager->getConfigPortalSSID());
    Serial.print("[WIFI MANAGER] Buka browser ke IP: ");
    Serial.println(WiFi.softAPIP());

    if (lcdAvailable) {
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("SETUP WIFI DARI HP:");
        lcd.setCursor(0, 1);
        lcd.print("SSID: ESP32-Kebun-AP");
        lcd.setCursor(0, 2);
        lcd.print("IP  : 192.168.4.1");
        lcd.setCursor(0, 3);
        lcd.print("PW  : kebun2026");
    }

    beepAlarm();
}

// ==============================================================================
// 8. RELAY & SAFETY CONTROL
// ==============================================================================
void safeSetSSR(bool turnOn) {
    if (turnOn && (emergencyStop || systemLockout || switchLogicError)) {
        digitalWrite(SSR_SOLENOID, LOW);
        ssrActive = false;
        return;
    }
    digitalWrite(SSR_SOLENOID, turnOn ? HIGH : LOW);
    if (ssrActive != turnOn) {
        ssrActive = turnOn;
        beepClick();
        Serial.printf("[RELAY] Solenoid Valve: %s\n", turnOn ? "ON (MENGISI)" : "OFF (STOP)");
    }
    if (!turnOn) fillingStartMs = 0;
}

// ==============================================================================
// 9. KALMAN & SENSOR FILTERS
// ==============================================================================
float movingAverageFilter(float v) {
    tds_history[ma_index] = v;
    ma_index = (ma_index + 1) % MA_WINDOW;
    float s = 0;
    for (int i = 0; i < MA_WINDOW; i++) s += tds_history[i];
    return s / MA_WINDOW;
}

float kalmanFilterTDS(float measurement) {
    static float last_measurement = 500.0f;
    float innovation = measurement - last_measurement;
    float R_use = (fabsf(innovation) > 10.0f) ? tds_kalman_error_measure * 3.0f : tds_kalman_error_measure;
    float P_pred = tds_kalman_error_estimate + tds_kalman_q;
    float K = P_pred / (P_pred + R_use);
    tds_kalman_estimate += K * (measurement - tds_kalman_estimate);
    tds_kalman_error_estimate = (1.0f - K) * P_pred;
    last_measurement = measurement;
    return tds_kalman_estimate;
}

float readSingleTDS(int tdsPin, float wTemp) {
    long sumRaw = 0;
    for (int i = 0; i < 20; i++) {
        sumRaw += analogRead(tdsPin);
        delay(2);
    }
    int raw = sumRaw / 20;
    if (raw <= 15) return 0.0f; // Kering di udara
    float v  = (float)raw * (3.3f / 4095.0f);
    float cc = 1.0f + 0.02f * (wTemp - 25.0f);
    float vc = v / cc;
    float ppm = (133.42f * vc * vc * vc - 255.86f * vc * vc + 857.39f * vc) * 0.5f * TDS_CALIBRATION_FACTOR;
    return (ppm < 0.0f) ? 0.0f : ppm;
}

float readTDSStable(int tdsPin, float wTemp, int samples, int delayMs) {
    float sum = 0;
    int validCount = 0;
    for (int i = 0; i < samples; i++) {
        float val = readSingleTDS(tdsPin, wTemp);
        sum += val;
        validCount++;
        delay(delayMs);
    }
    float avg = (validCount > 0) ? (sum / (float)validCount) : 0.0f;
    return movingAverageFilter(kalmanFilterTDS(avg));
}

float readPH() {
    long sumRaw = 0;
    for (int i = 0; i < 20; i++) {
        sumRaw += analogRead(PH_PIN);
        delay(2);
    }
    int raw = sumRaw / 20;
    float voltage = (float)raw * (3.3f / 4095.0f);
    return PH_CALIBRATION_SLOPE * voltage + PH_CALIBRATION_OFFSET;
}

float readPHStable(int samples, int delayMs) {
    float sum = 0;
    for (int i = 0; i < samples; i++) {
        sum += readPH();
        delay(delayMs);
    }
    return sum / (float)samples;
}

void resetKalmanFilter() {
    tds_kalman_estimate = 500.0f;
    tds_kalman_error_estimate = 1.0f;
    for (int i = 0; i < MA_WINDOW; i++) tds_history[i] = 500.0f;
    ma_index = 0;
    lastKalmanResetTime = millis();
}

// ==============================================================================
// 10. SENSOR READING (SAFE & ROBUST)
// ==============================================================================
void readAllSensors() {
    // DS18B20
    if (ds18b20Count > 0) {
        ds18b20.requestTemperatures();
        float wt = ds18b20.getTempCByIndex(0);
        if (wt > -50.0f && wt < 120.0f) {
            waterTemp = wt + WATER_TEMP_OFFSET;
        }
    }

    // DHT22
    float t = dht.readTemperature();
    float h = dht.readHumidity();
    if (!isnan(t) && t > -40.0f && t < 80.0f) airTemp  = t + DHT_TEMP_OFFSET;
    if (!isnan(h) && h >= 0.0f && h <= 100.0f) humidity = h + DHT_HUM_OFFSET;

    // pH & TDS
    float ph = readPHStable(2, 5);
    float tds = readTDSStable(TDS_PIN, waterTemp, 2, 5);
    if (ph >= 0.0f && ph <= 14.0f) phValue = ph;
    if (tds >= 0.0f && tds <= 5000.0f) tdsValue = tds;

    // Pelampung
    g_lowSwitch  = (digitalRead(WATER_SWITCH_LOW_PIN)  == LOW);
    g_highSwitch = (digitalRead(WATER_SWITCH_HIGH_PIN) == LOW);
}

// ==============================================================================
// 11. WATER LEVEL LOGIC & SOLENOID AUTOMATION
// ==============================================================================
void updateWaterControlState() {
    if (emergencyStop || systemLockout || switchLogicError) {
        waterControlState = STATE_EMERGENCY;
        safeSetSSR(false);
        return;
    }

    if (g_highSwitch && !g_lowSwitch) {
        switchLogicError = true;
        waterControlState = STATE_EMERGENCY;
        safeSetSSR(false);
        return;
    } else {
        switchLogicError = false;
    }

    if (g_highSwitch && g_lowSwitch) {
        waterControlState = STATE_FULL;
        if (ssrActive) safeSetSSR(false);
    } else if (!g_lowSwitch && !g_highSwitch) {
        waterControlState = STATE_FILLING;
        if (!ssrActive) {
            safeSetSSR(true);
            fillingStartMs = millis();
        }
    } else if (g_lowSwitch && !g_highSwitch) {
        waterControlState = STATE_BETWEEN;
    }

    if (ssrActive && fillingStartMs > 0) {
        if (millis() - fillingStartMs > MAX_FILLING_RUNTIME) {
            emergencyStop = true;
            emergencyStartTime = millis();
            emergencyCycleCount++;
            safeSetSSR(false);
            beepAlarm();
        }
    }
}

// ==============================================================================
// 12. FULL REST API TRANSMISSION TO WEBSITE
// ==============================================================================
void sendSensorDataViaREST() {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[REST] WiFi belum terhubung.");
        return;
    }

    HTTPClient http;
    String url = String(website_base_url) + "/api/save-sensor";
    Serial.printf("[REST] Mengirim ke %s ...\n", url.c_str());

    http.begin(clientSecure, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", website_api_key);
    http.setTimeout(6000);

    String waterLvlStr = "Sedang";
    if (systemLockout)          waterLvlStr = "LOCKOUT";
    else if (switchLogicError)   waterLvlStr = "ERROR";
    else if (g_highSwitch && g_lowSwitch)  waterLvlStr = "Penuh";
    else if (g_lowSwitch && !g_highSwitch) waterLvlStr = "Sedang";
    else                                   waterLvlStr = "Rendah";

    StaticJsonDocument<512> doc;
    doc["waterTemp"]   = roundf(waterTemp * 10.0f) / 10.0f;
    doc["ph"]          = roundf(phValue  * 10.0f) / 10.0f;
    doc["tds"]         = roundf(tdsValue * 10.0f) / 10.0f;
    doc["airTemp"]     = roundf(airTemp  * 10.0f) / 10.0f;
    doc["humidity"]    = roundf(humidity * 10.0f) / 10.0f;
    doc["water_level"] = waterLvlStr;

    String body;
    serializeJson(doc, body);

    int httpCode = http.POST(body);
    if (httpCode > 0) {
        String resp = http.getString();
        Serial.printf("[REST] Sukses! HTTP %d: %s\n", httpCode, resp.c_str());

        StaticJsonDocument<256> respDoc;
        DeserializationError err = deserializeJson(respDoc, resp);
        if (!err) {
            const char* cmd = respDoc["command"] | "AUTO";
            if (strcmp(cmd, "FORCE_FILL") == 0) {
                safeSetSSR(true);
            } else if (strcmp(cmd, "FORCE_STOP") == 0) {
                safeSetSSR(false);
            }
        }
    } else {
        Serial.printf("[REST] Gagal HTTP: %d (%s)\n", httpCode, http.errorToString(httpCode).c_str());
    }
    http.end();
}

void sendHardwareNotification(String type, String value) {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    String url = String(website_base_url) + "/api/notif-hardware";

    http.begin(clientSecure, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", website_api_key);
    http.setTimeout(6000);

    StaticJsonDocument<256> doc;
    doc["type"]  = type;
    doc["value"] = value;

    String body;
    serializeJson(doc, body);

    int httpCode = http.POST(body);
    if (httpCode == 200) {
        Serial.printf("[NOTIF WA] Terkirim: %s = %s\n", type.c_str(), value.c_str());
    }
    http.end();
}

void checkAndSendNotifications() {
    unsigned long now = millis();

    String currentLevel = (g_highSwitch && g_lowSwitch) ? "Penuh" : (g_lowSwitch ? "Sedang" : "Habis");
    if (currentLevel != lastNotifWaterLevel && (now - cooldownWaterLevel >= CD_WATER_LEVEL)) {
        cooldownWaterLevel = now;
        lastNotifWaterLevel = currentLevel;
        if (currentLevel == "Habis") {
            sendHardwareNotification("water_level", "Air Habis / Kritis");
            beepAlarm();
        }
    }

    if (ssrActive != lastNotifSolenoid && (now - cooldownSolenoid >= CD_SOLENOID)) {
        cooldownSolenoid = now;
        lastNotifSolenoid = ssrActive;
        sendHardwareNotification("solenoid", ssrActive ? "Pengisian Air Menyala (ON)" : "Pengisian Air Berhenti (OFF)");
    }
}

// ==============================================================================
// 13. LCD 20x4 DISPLAY
// ==============================================================================
void updateLCD() {
    if (!lcdAvailable) return;

    static int screen = 0;
    lcd.clear();

    if (screen == 0) {
        lcd.setCursor(0, 0);
        lcd.printf("T.Air:%4.1fC pH:%4.1f", waterTemp, phValue);
        lcd.setCursor(0, 1);
        lcd.printf("TDS  :%4.0f  Ud:%4.1f", tdsValue, airTemp);
        lcd.setCursor(0, 2);
        lcd.printf("Hum  :%4.1f%% Rly:%s", humidity, ssrActive ? "ON " : "OFF");
        lcd.setCursor(0, 3);
        String lvl = (g_highSwitch && g_lowSwitch) ? "Penuh " : (g_lowSwitch ? "Sedang" : "Kurang");
        lcd.printf("Level:%s WiFi:OK ", lvl.c_str());
        screen = 1;
    } else {
        DateTime nowRtc = rtcAvailable ? rtc.now() : DateTime(2026, 9, 13, 12, 0, 0);
        lcd.setCursor(0, 0);
        lcd.printf("TIME : %02d:%02d:%02d", nowRtc.hour(), nowRtc.minute(), nowRtc.second());
        lcd.setCursor(0, 1);
        lcd.printf("DATE : %02d/%02d/%04d", nowRtc.day(), nowRtc.month(), nowRtc.year());
        lcd.setCursor(0, 2);
        lcd.printf("VALVE: %s (%s)", ssrActive ? "ON " : "OFF", (waterControlState == STATE_FILLING) ? "FILL" : "IDLE");
        lcd.setCursor(0, 3);
        lcd.printf("IP   : %s", WiFi.localIP().toString().c_str());
        screen = 0;
    }
}

// ==============================================================================
// 14. ARDUINO SETUP
// ==============================================================================
void setup() {
    Serial.begin(115200);
    delay(300);
    Serial.println("\n\n========================================");
    Serial.println("  HARVESTHOUSE ESP32 - FULL REST API    ");
    Serial.println("========================================");

    setupBuzzer();
    beepSuccess();

    // Konfigurasi ADC ESP32 (12-bit & Jangkauan Penuh 3.3V)
    analogReadResolution(12);
    analogSetAttenuation(ADC_11db);

    pinMode(SSR_SOLENOID, OUTPUT);
    digitalWrite(SSR_SOLENOID, LOW);

    pinMode(WATER_SWITCH_LOW_PIN, INPUT_PULLUP);
    pinMode(WATER_SWITCH_HIGH_PIN, INPUT_PULLUP);

    // Setup SSL Client (Insecure to skip cert parsing)
    clientSecure.setInsecure();

    // Inisialisasi I2C 1 (LCD 20x4) dengan Hardware Scanner Cepat
    Wire.begin(SDA_PIN, SCL_PIN);
    Wire.setTimeOut(20);
    Wire.beginTransmission(0x27);
    byte err27 = Wire.endTransmission();
    Wire.beginTransmission(0x3F);
    byte err3F = Wire.endTransmission();

    if (err27 == 0 || err3F == 0) {
        int statusLcd = lcd.begin(20, 4);
        if (statusLcd == 0) {
            lcdAvailable = true;
            lcd.backlight();
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("HARVESTHOUSE v4.0");
            lcd.setCursor(0, 1);
            lcd.print("Memulai sistem...");
            Serial.println("[LCD] LCD 20x4 Terdeteksi.");
        }
    } else {
        lcdAvailable = false;
        Serial.println("[LCD] Layar LCD tidak terpasang (dilewati).");
    }

    // Inisialisasi I2C 2 (RTC DS3231)
    I2C_2.begin(SDA2_PIN, SCL2_PIN, 100000);
    I2C_2.setTimeOut(20);
    I2C_2.beginTransmission(0x68); // Alamat I2C standar DS3231
    if (I2C_2.endTransmission() == 0 && rtc.begin(&I2C_2)) {
        rtcAvailable = true;
        Serial.println("[RTC] DS3231 Siap.");
    } else {
        rtcAvailable = false;
        Serial.println("[RTC] DS3231 tidak terdeteksi (dilewati).");
    }

    dht.begin();
    pinMode(ONE_WIRE_BUS, INPUT_PULLUP);
    ds18b20.begin();
    ds18b20.setWaitForConversion(false); // Non-blocking
    ds18b20Count = ds18b20.getDeviceCount();
    Serial.printf("[DS18B20] Sensor suhu air terdeteksi: %d\n", ds18b20Count);

    // Membaca konfigurasi tersimpan dari Flash NVS
    preferences.begin("harvest", false);
    String saved_url = preferences.getString("server_url", website_base_url);
    if (saved_url.indexOf("lhr.life") >= 0 || saved_url.indexOf("localhost") >= 0 || saved_url.length() == 0) {
        saved_url = "https://harvesthouse.biz.id";
        preferences.putString("server_url", saved_url);
    }
    String saved_key = preferences.getString("api_key", website_api_key);
    if (saved_key.length() == 0) {
        saved_key = "HARVEST123";
        preferences.putString("api_key", saved_key);
    }
    strncpy(website_base_url, saved_url.c_str(), sizeof(website_base_url) - 1);
    strncpy(website_api_key, saved_key.c_str(), sizeof(website_api_key) - 1);

    // WiFiManager Setup
    WiFiManager wm;
    wm.setAPCallback(configModeCallback);
    wm.setConfigPortalTimeout(180);

    WiFiManagerParameter custom_server("server", "Website Base URL", website_base_url, 128);
    WiFiManagerParameter custom_key("apikey", "ESP32 API Key", website_api_key, 32);
    wm.addParameter(&custom_server);
    wm.addParameter(&custom_key);

    if (lcdAvailable) {
        lcd.setCursor(0, 2);
        lcd.print("Koneksi WiFi...");
    }
    bool wifiOk = wm.autoConnect("ESP32-Kebun-AP", "kebun2026");

    if (wifiOk) {
        if (strlen(custom_server.getValue()) > 0) {
            strncpy(website_base_url, custom_server.getValue(), sizeof(website_base_url) - 1);
            preferences.putString("server_url", String(website_base_url));
        }
        if (strlen(custom_key.getValue()) > 0) {
            strncpy(website_api_key, custom_key.getValue(), sizeof(website_api_key) - 1);
            preferences.putString("api_key", String(website_api_key));
        }
        Serial.printf("[WIFI] Terhubung! IP: %s\n", WiFi.localIP().toString().c_str());
        Serial.printf("[CONFIG] Server URL: %s\n", website_base_url);

        timeClient.begin();
        timeClient.update();
        if (timeClient.getEpochTime() > 100000 && rtcAvailable) {
            rtc.adjust(DateTime(timeClient.getEpochTime()));
        }
        beepSuccess();
    } else {
        Serial.println("[WIFI] Gagal terhubung / Timeout portal. Berjalan Offline.");
        beepAlarm();
    }
    preferences.end();

    // Inisialisasi Warmup Sensor (3 detik)
    warmup_start_time = millis();
    warmup_complete = false;
    unsigned long t0 = millis();
    previousSensorRead = t0;
    previousRestPost   = t0;
    previousLCDUpdate  = t0;
    lastSafetyCheck    = t0;
    lastKalmanResetTime= t0;

    Serial.println("[SETUP] Inisialisasi sukses! Memulai loop utama...");
}

// ==============================================================================
// 15. ARDUINO LOOP
// ==============================================================================
void loop() {
    unsigned long now = millis();

    // 1. Cek Warmup Sensor di 3 detik pertama
    if (!warmup_complete) {
        if (now - warmup_start_time >= WARMUP_DURATION) {
            warmup_complete = true;
            Serial.println("\n[SYSTEM] Warmup Selesai! Mulai transmisi data sensor...");
            beepSuccess();
        } else {
            delay(100);
            return;
        }
    }

    // 2. Baca Sensor Berkala (Tiap 3 Detik)
    if (now - previousSensorRead >= SENSOR_READ_INTERVAL) {
        previousSensorRead = now;
        readAllSensors();
    }

    // 3. Otomasi Kontrol Air & Solenoid
    updateWaterControlState();

    // 4. Cek & Kirim Notifikasi Bahaya
    checkAndSendNotifications();

    // 5. Kirim Data Sensor ke Website via REST API (Tiap 5 Detik)
    if (now - previousRestPost >= REST_POST_INTERVAL) {
        previousRestPost = now;
        sendSensorDataViaREST();
    }

    // 6. Update Tampilan LCD 20x4
    if (now - previousLCDUpdate >= LCD_UPDATE_INTERVAL) {
        previousLCDUpdate = now;
        updateLCD();
    }

    // 7. Reset Kalman Berkala
    if (now - lastKalmanResetTime >= KALMAN_RESET_INTERVAL) {
        resetKalmanFilter();
    }

    delay(20);
}
