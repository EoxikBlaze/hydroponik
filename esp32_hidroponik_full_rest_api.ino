#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <DHT.h>
#include <Wire.h>
#include <RTClib.h>
#include <ArduinoJson.h>
#include <hd44780.h>
#include <hd44780ioClass/hd44780_I2Cexp.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <NTPClient.h>
#include <WiFiUdp.h>
#include <HTTPClient.h>
#include <WiFiManager.h>      // Library WiFiManager by tzapu (Install via Arduino Library Manager)
#include <Preferences.h>      // Untuk simpan Server URL & API Key permanen di NVS Flash ESP32

// ==============================================================================
// KONFIGURASI SISTEM HIDROPONIK ESP32 - FULL REST API (NO MQTT / NO HIVEMQ)
// ==============================================================================
// GANTI BASE URL DI BAWAH INI:
// - Saat pengujian via localhost.run : "https://xxxx.localhost.run"
// - Saat di jaringan lokal laptop     : "http://192.168.1.xxx:8000"
// - Saat produksi / live              : "https://harvesthouse.biz.id"
// ==============================================================================
// KONFIGURASI SISTEM HIDROPONIK ESP32 - WIFIMANAGER + FULL REST API
// ==============================================================================
Preferences preferences;

// Nilai default (bisa diubah dari HP via WiFiManager portal tanpa reflash!)
char website_base_url[128] = "https://harvesthouse.biz.id";
char website_api_key[32]   = "HARVEST123";

// Callback saat ESP32 masuk mode Access Point (Hotspot)
void configModeCallback(WiFiManager *myWiFiManager) {
    Serial.println("
[WIFI MANAGER] Masuk Mode Access Point (Hotspot Setup)");
    Serial.print("[WIFI MANAGER] Hubungkan HP ke SSID: ");
    Serial.println(myWiFiManager->getConfigPortalSSID());
    Serial.print("[WIFI MANAGER] Buka browser ke IP: ");
    Serial.println(WiFi.softAPIP());

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("SETUP WIFI DARI HP:");
    lcd.setCursor(0, 1);
    lcd.print("SSID: HarvestHouse");
    lcd.setCursor(0, 2);
    lcd.print("IP  : 192.168.4.1");
    lcd.setCursor(0, 3);
    lcd.print("PW  : kebun2026");
}

// Interval pengiriman data sensor ke server via HTTP POST (milidetik)
#define SENSOR_POST_INTERVAL  5000UL  // Kirim data setiap 5 detik
unsigned long lastSensorPostTime = 0;

WiFiUDP   ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");

#define SSR_SOLENOID          25
#define WATER_SWITCH_LOW_PIN  27
#define WATER_SWITCH_HIGH_PIN 14
#define DHTPIN                4
#define DHTTYPE               DHT22

#define DHT_TEMP_OFFSET       1.4f 
#define DHT_HUM_OFFSET       -18.4f

#define ONE_WIRE_BUS          18
#define SDA_PIN               21
#define SCL_PIN               22
#define SDA2_PIN              16
#define SCL2_PIN              17
#define PH_PIN                33
#define TDS_PIN               34
#define MAX_FILLING_RUNTIME       5400000UL

#define SAFETY_CHECK_INTERVAL     3000UL
#define MAX_SWITCH_ERROR_DURATION 300000UL
#define EMERGENCY_COOLDOWN_MS     5000UL
#define MAX_EMERGENCY_CYCLES      3

bool          emergencyStop        = false;
unsigned long emergencyStartTime   = 0;
unsigned long lastSafetyCheck      = 0;

unsigned int  emergencyCycleCount  = 0;
bool          systemLockout        = false;
unsigned long lastEmergencyWarnMs  = 0;

#define DEBOUNCE_MS             150
#define LOW_EMPTY_CONFIRM_MS    5000UL

unsigned long bothLowStartMs = 0;
unsigned long fillingStartMs = 0;

bool          switchLogicError = false;
unsigned long errorStartTime   = 0;
#define SWITCH_ERROR_LOCKOUT_MS 5000UL

bool g_lowSwitch  = false;
bool g_highSwitch = false;

bool manualOverride = false;

bool          offlineModeActive = false;
bool          offlineLCDShown   = false;
unsigned long offlineStartTime  = 0;

// ===================================================
// ANTI-SPAM: COOLDOWN PER KONDISI DI ESP32
// Lapis pertama sebelum sampai ke website
// ===================================================

// Waktu terakhir notif berhasil dikirim per kondisi (millis)
unsigned long cooldownWaterLevel  = 0;
unsigned long cooldownSolenoid    = 0;
unsigned long cooldownPh          = 0;
unsigned long cooldownTds         = 0;
unsigned long cooldownSuhuAir     = 0;
unsigned long cooldownAirTemp     = 0;
unsigned long cooldownHumidity    = 0;
unsigned long cooldownEmergency   = 0;
unsigned long cooldownSwitchError = 0;
unsigned long cooldownLockout     = 0;

// Batas cooldown per kondisi (milidetik)
#define CD_WATER_LEVEL    300000UL    // 5 menit
#define CD_SOLENOID       60000UL     // 1 menit
#define CD_SENSOR         600000UL    // 10 menit
#define CD_EMERGENCY      900000UL    // 15 menit
#define CD_SWITCH_ERROR   900000UL    // 15 menit
#define CD_LOCKOUT        1800000UL   // 30 menit

// ===================================================
// STATE TRACKER NOTIFIKASI
// Notif hanya dikirim saat kondisi BERUBAH
// ===================================================
String  lastNotifWaterLevel    = "";
bool    lastNotifSolenoid      = false;
bool    lastNotifSolenoidSet   = false;
bool    lastNotifPhAlert       = false;
bool    lastNotifTdsAlert      = false;
bool    lastNotifSuhuAlert     = false;
bool    lastNotifAirTempAlert  = false;
bool    lastNotifHumidityAlert = false;
bool    lastNotifEmergency     = false;
bool    lastNotifSwitchError   = false;
bool    lastNotifLockout       = false;

// ===================================================
// THRESHOLD - DARI DATABASE WEBSITE
// ===================================================
struct SensorThreshold {
    float minVal;
    float maxVal;
    bool  loaded;
};

SensorThreshold th_waterTemp = { 20.0f,  35.0f,  false };
SensorThreshold th_ph        = {  5.5f,   6.5f,  false };
SensorThreshold th_tds       = { 500.0f, 1000.0f, false };
SensorThreshold th_airTemp   = { 20.0f,  40.0f,  false };
SensorThreshold th_humidity  = { 50.0f,  90.0f,  false };

#define THRESHOLD_FETCH_INTERVAL  600000UL
unsigned long lastThresholdFetch = 0;
bool          thresholdLoaded    = false;

DHT                dht(DHTPIN, DHTTYPE);
OneWire            oneWire(ONE_WIRE_BUS);
DallasTemperature  ds18b20(&oneWire);
RTC_DS3231         rtc;
hd44780_I2Cexp     lcd;

#define PH_CALIBRATION_OFFSET   20.5910f
#define PH_CALIBRATION_SLOPE   -5.4006f
#define TDS_CALIBRATION_FACTOR  1.27f
#define WATER_TEMP_OFFSET       0.4f

float tds_kalman_estimate       = 0;
float tds_kalman_error_estimate = 1.0f;
float tds_kalman_error_measure  = 15.0f;
float tds_kalman_q              = 0.1f;

#define KALMAN_RESET_INTERVAL   300000UL
unsigned long lastKalmanResetTime = 0;

#define MA_WINDOW 5
float tds_history[MA_WINDOW] = {0};
int   ma_index = 0;

#define WARMUP_DURATION        30000UL
bool          warmup_complete         = false;
unsigned long warmup_start_time       = 0;
float         initial_tds_value       = 0;
float         initial_ph_value        = 0;
float         initial_water_temp      = 0;
bool          initial_values_captured = false;

#define MQTT_TOPIC_SUHU_AIR        "hydroponics/sensors/suhu_air"
#define MQTT_TOPIC_PH              "hydroponics/sensors/ph"
#define MQTT_TOPIC_TDS             "hydroponics/sensors/tds"
#define MQTT_TOPIC_SUHU_UDARA      "hydroponics/sensors/temperature"
#define MQTT_TOPIC_KELEMBAPAN      "hydroponics/sensors/humidity"
#define MQTT_TOPIC_RELAY           "hydroponics/relay/#"
#define MQTT_TOPIC_SYSTEM_STATUS   "hydroponics/system/status"
#define MQTT_TOPIC_WATER_LEVEL     "hydroponics/sensors/water_level"
#define MQTT_TOPIC_RESET_EMERGENCY "hydroponics/system/reset_emergency"
#define MQTT_TOPIC_MANUAL_OVERRIDE "hydroponics/system/manual_override"

float waterTemp  = 0;
float airTemp    = 0;
float humidity   = 0;
float phValue    = 0;
float tdsValue   = 0;
float waterLevel = 0;

bool  ssrActive  = false;

unsigned long previousSensorRead = 0;
unsigned long previousPublish    = 0;
unsigned long previousLCDUpdate  = 0;
unsigned long previousMQTTLoop   = 0;
unsigned long previousTDSRead    = 0;

const unsigned long SENSOR_READ_INTERVAL = 3000UL;
const unsigned long PUBLISH_INTERVAL     = 5000UL;
const unsigned long LCD_UPDATE_INTERVAL  = 3000UL;
const unsigned long MQTT_LOOP_INTERVAL   = 100UL;
const unsigned long TDS_READ_INTERVAL    = 2000UL;

enum WaterControlState {
    STATE_IDLE,
    STATE_FILLING,
    STATE_FULL,
    STATE_BETWEEN,
    STATE_EMERGENCY
};
WaterControlState waterControlState = STATE_IDLE;
unsigned long     stateChangeTime   = 0;
bool              fillingActive     = false;

// ===================================================
// FORWARD DECLARATIONS
// ===================================================
void syncRTCWithNTP();
void reconnectWiFi();
void setupSSRPin();
void safeSetSSR(bool turnOn);
float readPH();
float readTDSStable(int tdsPin, float wTemp, int samples, int delayMs);
void updateLCD();
void publishSSRState(bool state);
void turnOffSSR();
void readAllSensors();
float readPHStable(int samples, int delayMs);
float kalmanFilterTDS(float measurement);
float readSingleTDS(int tdsPin, float wTemp);
float movingAverageFilter(float new_value);
void initializeWarmup();
void checkWarmupStatus();
void displayWarmupScreen();
void checkSafetySystems();
void tryResetEmergency(bool forcedByMQTT);
void resetKalmanFilter();
void checkFilterStatus();
bool readLowSwitch();
bool readHighSwitch();
bool validateSwitchLogic(bool lowFull, bool highFull);
void updateWaterControlState();
void controlWaterSystem();
void logWaterControlDebug();
void updateGlobalSwitchState();
bool isOnline();
void handleOfflineMode();
void handleBackOnline();
void fetchThresholdsFromWebsite();
void sendHardwareNotif(const String& type, const String& value, const String& customMsg);
void checkAndSendNotifications();
void resetNotifTrackers();

// ===================================================
// RESET SEMUA TRACKER NOTIFIKASI
// Dipanggil saat online kembali setelah offline
// ===================================================
void resetNotifTrackers() {
    lastNotifWaterLevel    = "";
    lastNotifSolenoidSet   = false;
    lastNotifPhAlert       = false;
    lastNotifTdsAlert      = false;
    lastNotifSuhuAlert     = false;
    lastNotifAirTempAlert  = false;
    lastNotifHumidityAlert = false;
    lastNotifEmergency     = false;
    lastNotifSwitchError   = false;
    lastNotifLockout       = false;

    // Cooldown TIDAK direset saat online kembali
    // agar tidak spam setelah koneksi pulih
    Serial.println("[NOTIF] Tracker kondisi direset, cooldown dipertahankan");
}

// ===================================================
// KIRIM NOTIFIKASI KE WEBSITE
// Satu-satunya fungsi yang boleh kirim HTTP ke website
// Semua notifikasi (termasuk emergency) lewat sini
//
// Anti-spam lapis 1: cooldown per kondisi di ESP32
// Anti-spam lapis 2: bolehKirim() di AlertLogModel
// ===================================================
void sendHardwareNotif(const String& type, const String& value,
                       const String& customMsg, unsigned long& cooldownRef,
                       unsigned long cooldownMs) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.printf("[NOTIF] Skip %s: WiFi offline\n", type.c_str());
        return;
    }

    unsigned long now = millis();

    // Cek cooldown ESP32 (lapis 1)
    if (cooldownRef > 0 && now - cooldownRef < cooldownMs) {
        unsigned long sisaDetik = (cooldownMs - (now - cooldownRef)) / 1000UL;
        Serial.printf("[NOTIF] Skip %s: cooldown %lu detik lagi\n",
            type.c_str(), sisaDetik);
        return;
    }

    Serial.printf("[NOTIF] Kirim: type=%s value=%s\n", type.c_str(), value.c_str());

    HTTPClient http;
    WiFiClientSecure clientSecure;
    clientSecure.setInsecure();

    http.begin(clientSecure, (String(website_base_url) + "/api/notif-hardware").c_str());
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(8000);

    StaticJsonDocument<300> doc;
    doc["api_key"] = website_api_key;
    doc["type"]    = type;
    doc["value"]   = value;
    if (customMsg.length() > 0) {
        doc["message"] = customMsg;
    }

    char body[300];
    serializeJson(doc, body);

    int httpCode = http.POST(body);

    if (httpCode > 0) {
        String response = http.getString();
        Serial.printf("[NOTIF] HTTP %d: %s\n", httpCode, response.c_str());

        // Update cooldown hanya jika HTTP berhasil (bukan error jaringan)
        // Status "cooldown" dari website juga dianggap berhasil
        if (httpCode == 200) {
            cooldownRef = now;
        }
    } else {
        Serial.printf("[NOTIF] HTTP Error: %s\n", http.errorToString(httpCode).c_str());
        // Jangan update cooldown jika gagal kirim
        // agar bisa dicoba lagi berikutnya
    }

    http.end();
}

// ===================================================
// CEK DAN KIRIM NOTIFIKASI
// Dipanggil di loop() setelah sensor dibaca
// ===================================================
void checkAndSendNotifications() {
    if (!warmup_complete) return;
    if (!isOnline())      return;

    // -----------------------------------------------
    // 1. WATER LEVEL
    // Kirim hanya saat status BERUBAH
    // -----------------------------------------------
    String currentWaterLevel = "Kurang";
    if      (g_highSwitch && g_lowSwitch)  currentWaterLevel = "Penuh";
    else if (g_lowSwitch && !g_highSwitch) currentWaterLevel = "Sedang";

    if (currentWaterLevel != lastNotifWaterLevel) {
        Serial.printf("[NOTIF] Water level berubah: '%s' -> '%s'\n",
            lastNotifWaterLevel.c_str(), currentWaterLevel.c_str());
        sendHardwareNotif("water_level", currentWaterLevel, "",
            cooldownWaterLevel, CD_WATER_LEVEL);
        lastNotifWaterLevel = currentWaterLevel;
    }

    // -----------------------------------------------
    // 2. SOLENOID ON/OFF
    // Kirim hanya saat status BERUBAH
    // -----------------------------------------------
    if (!lastNotifSolenoidSet || ssrActive != lastNotifSolenoid) {
        if (lastNotifSolenoidSet) {
            String val = ssrActive ? "ON" : "OFF";
            Serial.printf("[NOTIF] Solenoid berubah -> %s\n", val.c_str());
            sendHardwareNotif("solenoid", val, "",
                cooldownSolenoid, CD_SOLENOID);
        }
        lastNotifSolenoid    = ssrActive;
        lastNotifSolenoidSet = true;
    }

    // -----------------------------------------------
    // 3. pH ABNORMAL
    // Kirim saat masuk kondisi abnormal (reset saat normal)
    // -----------------------------------------------
    bool phAbnormal = (phValue < th_ph.minVal || phValue > th_ph.maxVal);
    if (phAbnormal && !lastNotifPhAlert) {
        Serial.printf("[NOTIF] pH abnormal: %.2f (batas: %.1f-%.1f)\n",
            phValue, th_ph.minVal, th_ph.maxVal);
        sendHardwareNotif("ph", String(phValue, 2), "",
            cooldownPh, CD_SENSOR);
        lastNotifPhAlert = true;
    } else if (!phAbnormal) {
        lastNotifPhAlert = false;
    }

    // -----------------------------------------------
    // 4. TDS ABNORMAL
    // -----------------------------------------------
    bool tdsAbnormal = (tdsValue < th_tds.minVal || tdsValue > th_tds.maxVal);
    if (tdsAbnormal && !lastNotifTdsAlert) {
        Serial.printf("[NOTIF] TDS abnormal: %.1f (batas: %.1f-%.1f)\n",
            tdsValue, th_tds.minVal, th_tds.maxVal);
        sendHardwareNotif("tds", String((int)tdsValue), "",
            cooldownTds, CD_SENSOR);
        lastNotifTdsAlert = true;
    } else if (!tdsAbnormal) {
        lastNotifTdsAlert = false;
    }

    // -----------------------------------------------
    // 5. SUHU AIR ABNORMAL
    // -----------------------------------------------
    bool suhuAirAbnormal = (waterTemp < th_waterTemp.minVal || waterTemp > th_waterTemp.maxVal);
    if (suhuAirAbnormal && !lastNotifSuhuAlert) {
        Serial.printf("[NOTIF] Suhu air abnormal: %.1f (batas: %.1f-%.1f)\n",
            waterTemp, th_waterTemp.minVal, th_waterTemp.maxVal);
        sendHardwareNotif("suhu_air", String(waterTemp, 1), "",
            cooldownSuhuAir, CD_SENSOR);
        lastNotifSuhuAlert = true;
    } else if (!suhuAirAbnormal) {
        lastNotifSuhuAlert = false;
    }

    // -----------------------------------------------
    // 6. SUHU UDARA ABNORMAL
    // -----------------------------------------------
    if (!isnan(airTemp)) {
        bool airTempAbnormal = (airTemp < th_airTemp.minVal || airTemp > th_airTemp.maxVal);
        if (airTempAbnormal && !lastNotifAirTempAlert) {
            Serial.printf("[NOTIF] Suhu udara abnormal: %.1f (batas: %.1f-%.1f)\n",
                airTemp, th_airTemp.minVal, th_airTemp.maxVal);
            sendHardwareNotif("airTemp", String(airTemp, 1), "",
                cooldownAirTemp, CD_SENSOR);
            lastNotifAirTempAlert = true;
        } else if (!airTempAbnormal) {
            lastNotifAirTempAlert = false;
        }
    }

    // -----------------------------------------------
    // 7. KELEMBAPAN ABNORMAL
    // -----------------------------------------------
    if (!isnan(humidity)) {
        bool humidityAbnormal = (humidity < th_humidity.minVal || humidity > th_humidity.maxVal);
        if (humidityAbnormal && !lastNotifHumidityAlert) {
            Serial.printf("[NOTIF] Kelembapan abnormal: %.1f (batas: %.1f-%.1f)\n",
                humidity, th_humidity.minVal, th_humidity.maxVal);
            sendHardwareNotif("humidity", String(humidity, 1), "",
                cooldownHumidity, CD_SENSOR);
            lastNotifHumidityAlert = true;
        } else if (!humidityAbnormal) {
            lastNotifHumidityAlert = false;
        }
    }

    // -----------------------------------------------
    // 8. SWITCH LOGIC ERROR
    // Kirim saat error baru muncul, cooldown 15 menit
    // -----------------------------------------------
    if (switchLogicError && !lastNotifSwitchError) {
        Serial.println("[NOTIF] Switch logic error!");
        sendHardwareNotif("emergency",
            "Switch logic error",
            "Sensor bawah KURANG tapi sensor atas PENUH. SSR dibekukan!",
            cooldownSwitchError, CD_SWITCH_ERROR);
        lastNotifSwitchError = true;
    } else if (!switchLogicError) {
        lastNotifSwitchError = false;
    }

    // -----------------------------------------------
    // 9. SYSTEM LOCKOUT
    // Kirim saat lockout baru terjadi, cooldown 30 menit
    // -----------------------------------------------
    if (systemLockout && !lastNotifLockout) {
        Serial.println("[NOTIF] System lockout!");
        sendHardwareNotif("emergency",
            "System Lockout",
            "Terlalu banyak siklus emergency. Reset via MQTT: hydroponics/system/reset_emergency = RESET",
            cooldownLockout, CD_LOCKOUT);
        lastNotifLockout = true;
    } else if (!systemLockout) {
        lastNotifLockout = false;
    }
}

// ===================================================
// FETCH THRESHOLD DARI WEBSITE
// ===================================================
void fetchThresholdsFromWebsite() {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[THRESHOLD] Skip: WiFi offline");
        return;
    }

    Serial.println("[THRESHOLD] Mengambil dari website...");

    HTTPClient http;
    WiFiClientSecure clientSecure;
    clientSecure.setInsecure();

    http.begin(clientSecure, (String(website_base_url) + "/api/get-thresholds?api_key=" + website_api_key).c_str());
    http.setTimeout(8000);

    int httpCode = http.GET();

    if (httpCode != 200) {
        Serial.printf("[THRESHOLD] HTTP %d, pakai nilai lama\n", httpCode);
        http.end();
        return;
    }

    String payload = http.getString();
    http.end();

    StaticJsonDocument<512> doc;
    DeserializationError err = deserializeJson(doc, payload);

    if (err || !doc["status"].as<bool>()) {
        Serial.println("[THRESHOLD] Parse gagal atau status false");
        return;
    }

    JsonObject t = doc["thresholds"];

    if (t.containsKey("waterTemp")) {
        th_waterTemp.minVal = t["waterTemp"]["min"].as<float>();
        th_waterTemp.maxVal = t["waterTemp"]["max"].as<float>();
        th_waterTemp.loaded = true;
    }
    if (t.containsKey("ph")) {
        th_ph.minVal = t["ph"]["min"].as<float>();
        th_ph.maxVal = t["ph"]["max"].as<float>();
        th_ph.loaded = true;
    }
    if (t.containsKey("tds")) {
        th_tds.minVal = t["tds"]["min"].as<float>();
        th_tds.maxVal = t["tds"]["max"].as<float>();
        th_tds.loaded = true;
    }
    if (t.containsKey("airTemp")) {
        th_airTemp.minVal = t["airTemp"]["min"].as<float>();
        th_airTemp.maxVal = t["airTemp"]["max"].as<float>();
        th_airTemp.loaded = true;
    }
    if (t.containsKey("humidity")) {
        th_humidity.minVal = t["humidity"]["min"].as<float>();
        th_humidity.maxVal = t["humidity"]["max"].as<float>();
        th_humidity.loaded = true;
    }

    thresholdLoaded    = true;
    lastThresholdFetch = millis();

    Serial.println("[THRESHOLD] Berhasil diperbarui:");
    Serial.printf("  waterTemp : %.1f - %.1f\n", th_waterTemp.minVal, th_waterTemp.maxVal);
    Serial.printf("  pH        : %.1f - %.1f\n", th_ph.minVal,        th_ph.maxVal);
    Serial.printf("  TDS       : %.1f - %.1f\n", th_tds.minVal,       th_tds.maxVal);
    Serial.printf("  airTemp   : %.1f - %.1f\n", th_airTemp.minVal,   th_airTemp.maxVal);
    Serial.printf("  humidity  : %.1f - %.1f\n", th_humidity.minVal,  th_humidity.maxVal);
}

bool isOnline() {
    return (WiFi.status() == WL_CONNECTED);
}

void handleOfflineMode() {
    if (ssrActive) {
        digitalWrite(SSR_SOLENOID, LOW);
        ssrActive = false; fillingActive = false;
        bothLowStartMs = 0; fillingStartMs = 0;
        Serial.println("[OFFLINE] Solenoid OFF paksa");
    }

    if (waterControlState == STATE_FILLING) {
        waterControlState = STATE_IDLE;
        fillingStartMs    = 0;
    }

    if (manualOverride) {
        manualOverride = false;
    }

    if (!offlineModeActive) {
        offlineModeActive = true;
        offlineLCDShown   = false;
        offlineStartTime  = millis();
        bothLowStartMs    = 0;
        fillingStartMs    = 0;
        Serial.println("[OFFLINE] Mode offline aktif");
    }

    if (!offlineLCDShown) {
        offlineLCDShown = true;
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("!!! OFFLINE !!!!");
        lcd.setCursor(0, 1); lcd.print("Solenoid: OFF   ");
        lcd.setCursor(0, 2); lcd.print("Reconnecting... ");
        lcd.setCursor(0, 3); lcd.print("State: FROZEN   ");
    }
}

void handleBackOnline() {
    if (!offlineModeActive) return;

    unsigned long downtime = millis() - offlineStartTime;
    offlineModeActive = false;
    offlineLCDShown   = false;

    Serial.printf("[ONLINE] Pulih setelah %lu ms\n", downtime);

    waterControlState = STATE_IDLE;
    fillingActive     = false;
    bothLowStartMs    = 0;
    fillingStartMs    = 0;

    // Reset tracker kondisi (bukan cooldown)
    resetNotifTrackers();

    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("ONLINE KEMBALI! ");
    lcd.setCursor(0, 1); lcd.print("State: IDLE     ");
    lcd.setCursor(0, 2); lcd.print("Solenoid: OFF   ");
    lcd.setCursor(0, 3); lcd.print("Resuming...     ");
    delay(1500);
}

void updateGlobalSwitchState() {
    g_lowSwitch  = readLowSwitch();
    g_highSwitch = readHighSwitch();
}

bool readLowSwitch() {
    static int           lastStable = LOW;
    static bool          debouncing = false;
    static unsigned long changeMs   = 0;
    static bool          firstRun   = true;

    if (firstRun) {
        lastStable = digitalRead(WATER_SWITCH_LOW_PIN);
        firstRun   = false;
        Serial.printf("[LOW SW] Boot: %s\n", lastStable == HIGH ? "ADA" : "KURANG");
    }

    int current = digitalRead(WATER_SWITCH_LOW_PIN);
    unsigned long now = millis();

    if (current != lastStable) {
        if (!debouncing) { debouncing = true; changeMs = now; }
        else if (now - changeMs > DEBOUNCE_MS) {
            lastStable = current; debouncing = false;
            Serial.printf("[LOW SW] -> %s\n", lastStable == HIGH ? "ADA" : "KURANG");
        }
    } else { debouncing = false; }

    return (lastStable == HIGH);
}

bool readHighSwitch() {
    static int           lastStable = LOW;
    static bool          debouncing = false;
    static unsigned long changeMs   = 0;
    static bool          firstRun   = true;

    if (firstRun) {
        lastStable = digitalRead(WATER_SWITCH_HIGH_PIN);
        firstRun   = false;
        Serial.printf("[HIGH SW] Boot: %s\n", lastStable == HIGH ? "PENUH" : "BELUM");
    }

    int current = digitalRead(WATER_SWITCH_HIGH_PIN);
    unsigned long now = millis();

    if (current != lastStable) {
        if (!debouncing) { debouncing = true; changeMs = now; }
        else if (now - changeMs > DEBOUNCE_MS) {
            lastStable = current; debouncing = false;
            Serial.printf("[HIGH SW] -> %s\n", lastStable == HIGH ? "PENUH" : "BELUM");
        }
    } else { debouncing = false; }

    return (lastStable == HIGH);
}

bool validateSwitchLogic(bool lowFull, bool highFull) {
    if (!lowFull && highFull) {
        if (!switchLogicError) {
            switchLogicError = true;
            errorStartTime   = millis();
            bothLowStartMs   = 0;
            fillingStartMs   = 0;
            Serial.println("!!! SWITCH LOGIC ERROR: bawah=KURANG tapi atas=PENUH!");
            if (ssrActive) {
                digitalWrite(SSR_SOLENOID, LOW);
                ssrActive = false;
                publishSSRState(false);
            }
        }
        if (millis() - errorStartTime > MAX_SWITCH_ERROR_DURATION) {
            switchLogicError   = false;
            waterControlState  = STATE_EMERGENCY;
            emergencyStop      = true;
            emergencyStartTime = millis();
        }
        return false;
    }

    if (switchLogicError) {
        unsigned long cd = millis() - errorStartTime;
        if (cd < SWITCH_ERROR_LOCKOUT_MS) {
            if (ssrActive) { digitalWrite(SSR_SOLENOID, LOW); ssrActive = false; publishSSRState(false); }
            return false;
        }
        switchLogicError = false;
        Serial.println("Switch logic OK kembali.");
    }

    return true;
}

void tryResetEmergency(bool forcedByMQTT) {
    if (!emergencyStop) return;

    unsigned long now     = millis();
    unsigned long elapsed = now - emergencyStartTime;

    if (elapsed < EMERGENCY_COOLDOWN_MS) return;

    if (systemLockout && !forcedByMQTT) return;

    if (forcedByMQTT) {
        emergencyStop = false; emergencyStartTime = 0;
        systemLockout = false; emergencyCycleCount = 0;
        fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
        waterControlState = STATE_IDLE;
        lastNotifEmergency = false;
        lastNotifLockout   = false;
        Serial.println("[EMERGENCY] Reset via MQTT");

        sendHardwareNotif("emergency", "Reset",
            "Sistem di-reset manual via MQTT. Kembali normal.",
            cooldownEmergency, 0UL); // 0 = abaikan cooldown untuk notif reset
        return;
    }

    if (g_highSwitch) {
        emergencyStop = false; emergencyStartTime = 0;
        emergencyCycleCount = 0; fillingActive = false;
        bothLowStartMs = 0; fillingStartMs = 0;
        waterControlState = STATE_IDLE;
        lastNotifEmergency = false;
        Serial.println("[EMERGENCY] Reset — air penuh.");
        return;
    }

    emergencyCycleCount++;
    Serial.printf("[EMERGENCY] Cycle %u/%u\n", emergencyCycleCount, MAX_EMERGENCY_CYCLES);

    if (emergencyCycleCount >= MAX_EMERGENCY_CYCLES) {
        systemLockout = true;
        Serial.println("[EMERGENCY] MAX CYCLES, SYSTEM LOCKOUT!");
    }
}

void updateWaterControlState() {
    if (manualOverride || offlineModeActive) return;

    if (emergencyStop || systemLockout) {
        waterControlState = STATE_EMERGENCY;
        fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
        return;
    }

    bool lowFull  = g_lowSwitch;
    bool highFull = g_highSwitch;

    if (!validateSwitchLogic(lowFull, highFull)) {
        fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
        return;
    }

    unsigned long now = millis();

    if (lowFull && highFull) {
        if (waterControlState != STATE_FULL) Serial.println("STATE -> FULL");
        waterControlState = STATE_FULL;
        fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
        return;
    }

    if (lowFull && !highFull) {
        if (waterControlState == STATE_FILLING) {
            if (fillingStartMs == 0) fillingStartMs = now;
            return;
        }
        if (waterControlState != STATE_BETWEEN) Serial.println("STATE -> BETWEEN");
        waterControlState = STATE_BETWEEN;
        fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
        return;
    }

    if (!lowFull && !highFull) {
        if (bothLowStartMs == 0) {
            bothLowStartMs = now; waterControlState = STATE_IDLE;
            fillingActive = false; fillingStartMs = 0;
            Serial.println("AIR KURANG, tunggu 5 detik...");
            return;
        }
        if (now - bothLowStartMs >= LOW_EMPTY_CONFIRM_MS) {
            if (waterControlState != STATE_FILLING) {
                Serial.println("STATE -> FILLING"); fillingStartMs = 0;
            }
            waterControlState = STATE_FILLING; fillingActive = true; stateChangeTime = now;
        } else {
            waterControlState = STATE_IDLE; fillingActive = false;
        }
        return;
    }

    fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
}

void controlWaterSystem() {
    if (offlineModeActive) {
        if (ssrActive) { digitalWrite(SSR_SOLENOID, LOW); ssrActive = false; fillingStartMs = 0; publishSSRState(false); }
        return;
    }

    if (manualOverride) {
        if ((emergencyStop || systemLockout || switchLogicError) && ssrActive) {
            digitalWrite(SSR_SOLENOID, LOW); ssrActive = false; fillingStartMs = 0; publishSSRState(false);
        }
        return;
    }

    if (switchLogicError || systemLockout) {
        if (ssrActive) { digitalWrite(SSR_SOLENOID, LOW); ssrActive = false; fillingStartMs = 0; publishSSRState(false); }
        return;
    }

    if (emergencyStop) { if (ssrActive) safeSetSSR(false); return; }

    switch (waterControlState) {
        case STATE_FILLING:
            if (fillingActive && !ssrActive) { safeSetSSR(true); }
            break;
        case STATE_BETWEEN:
        case STATE_FULL:
        case STATE_IDLE:
        case STATE_EMERGENCY:
            if (ssrActive) safeSetSSR(false);
            break;
    }
}

void logWaterControlDebug() {
    static unsigned long lastLog = 0;
    unsigned long now = millis();
    if (now - lastLog < 10000UL) return;
    lastLog = now;

    Serial.println("\n=== WATER CONTROL DEBUG ===");
    Serial.printf("Switch BAWAH  : %s\n", g_lowSwitch      ? "ADA"    : "KURANG");
    Serial.printf("Switch ATAS   : %s\n", g_highSwitch     ? "PENUH"  : "BELUM");
    Serial.printf("SwitchError   : %s\n", switchLogicError ? "ERROR"  : "OK");
    Serial.printf("FillingActive : %s\n", fillingActive    ? "YES"    : "NO");
    Serial.printf("ManualOverride: %s\n", manualOverride   ? "AKTIF"  : "OFF");
    Serial.printf("OfflineMode   : %s\n", offlineModeActive? "AKTIF"  : "OK");
    Serial.printf("Solenoid SSR  : %s\n", ssrActive        ? "ON"     : "OFF");
    Serial.printf("Emergency     : %s\n", emergencyStop    ? "YES"    : "NO");
    Serial.printf("SystemLockout : %s\n", systemLockout    ? "LOCKED" : "OK");
    Serial.printf("Emrg Cycles   : %u/%u\n", emergencyCycleCount, MAX_EMERGENCY_CYCLES);
    Serial.printf("Threshold     : %s\n", thresholdLoaded  ? "DARI DB": "DEFAULT");
    Serial.print("State         : ");
    switch (waterControlState) {
        case STATE_IDLE:      Serial.println("IDLE");      break;
        case STATE_FILLING:   Serial.println("FILLING");   break;
        case STATE_FULL:      Serial.println("FULL");      break;
        case STATE_BETWEEN:   Serial.println("BETWEEN");   break;
        case STATE_EMERGENCY: Serial.println("EMERGENCY"); break;
    }
    Serial.println("===========================\n");
}

void checkSafetySystems() {
    unsigned long now = millis();

    if (!manualOverride && waterControlState == STATE_FILLING && ssrActive && fillingStartMs > 0) {
        if (now - fillingStartMs > MAX_FILLING_RUNTIME) {
            Serial.println("!!! EMERGENCY STOP: solenoid nyala terlalu lama !!!");
            digitalWrite(SSR_SOLENOID, LOW);
            ssrActive = false; fillingActive = false; fillingStartMs = 0;
            bothLowStartMs = 0; manualOverride = false;
            emergencyStop = true; emergencyStartTime = now;
            waterControlState = STATE_EMERGENCY;

            // Kirim notif emergency lewat website (bukan hardcoded)
            sendHardwareNotif("emergency",
                "Solenoid nyala " + String(MAX_FILLING_RUNTIME / 60000UL) + " menit tanpa air penuh",
                "Cek solenoid, sumber air, dan switch atas",
                cooldownEmergency, CD_EMERGENCY);

            lastNotifEmergency = true;
        }
    }
}

void safeSetSSR(bool turnOn) {
    if (offlineModeActive && turnOn) return;
    if ((emergencyStop || systemLockout) && turnOn) return;
    if (switchLogicError && turnOn) return;

    digitalWrite(SSR_SOLENOID, turnOn ? HIGH : LOW);
    ssrActive = turnOn;
    if (!turnOn) fillingStartMs = 0;
    publishSSRState(turnOn);
    Serial.printf("SSR: %s\n", turnOn ? "ON" : "OFF");
}

void resetKalmanFilter() {
    float rawNow = readSingleTDS(TDS_PIN, waterTemp);
    tds_kalman_estimate = rawNow; tds_kalman_error_estimate = 1.0f;
    for (int i = 0; i < MA_WINDOW; i++) tds_history[i] = rawNow;
    ma_index = 0; lastKalmanResetTime = millis();
    Serial.printf("Kalman reset @ %.1f ppm\n", rawNow);
}

float movingAverageFilter(float v) {
    tds_history[ma_index] = v;
    ma_index = (ma_index + 1) % MA_WINDOW;
    float s = 0;
    for (int i = 0; i < MA_WINDOW; i++) s += tds_history[i];
    return s / MA_WINDOW;
}

float kalmanFilterTDS(float measurement) {
    static float last_measurement = 0;
    float innovation = measurement - last_measurement;
    float R_use = (fabsf(innovation) > 10.0f) ? tds_kalman_error_measure * 3.0f : tds_kalman_error_measure;
    float P_pred = tds_kalman_error_estimate + tds_kalman_q;
    float K = P_pred / (P_pred + R_use);
    tds_kalman_estimate += K * (measurement - tds_kalman_estimate);
    tds_kalman_error_estimate = (1.0f - K) * P_pred;
    last_measurement = measurement;
    return tds_kalman_estimate;
}

float readPH() {
    int raw = analogRead(PH_PIN);
    float voltage = (float)raw * 3.3f / 4095.0f;
    return PH_CALIBRATION_SLOPE * voltage + PH_CALIBRATION_OFFSET;
}

float readPHStable(int samples, int delayMs) {
    float sum = 0;
    for (int i = 0; i < samples; i++) {
        sum += readPH();
        unsigned long t0 = millis();
        while (millis() - t0 < (unsigned long)delayMs) { mqttClient.loop(); delay(5); }
    }
    return sum / samples;
}

float readSingleTDS(int tdsPin, float wTemp) {
    int raw = 0;
    for (int j = 0; j < 5; j++) { raw += analogRead(tdsPin); delayMicroseconds(100); }
    raw /= 5;
    float v  = raw * (3.3f / 4095.0f);
    float cc = 1.0f + 0.02f * (wTemp - 25.0f);
    float vc = v / cc;
    return (133.42f * vc * vc * vc - 255.86f * vc * vc + 857.39f * vc) * TDS_CALIBRATION_FACTOR;
}

float readTDSStable(int tdsPin, float wTemp, int samples, int delayMs) {
    float sum = 0;
    for (int i = 0; i < samples; i++) {
        sum += readSingleTDS(tdsPin, wTemp);
        unsigned long t0 = millis();
        while (millis() - t0 < (unsigned long)delayMs) { mqttClient.loop(); delay(5); }
    }
    float avg = sum / samples;
    return movingAverageFilter(kalmanFilterTDS(avg));
}

void initializeWarmup() {
    warmup_complete = false; initial_values_captured = false;
    warmup_start_time = millis(); fillingActive = false;
    bothLowStartMs = 0; fillingStartMs = 0;
    ds18b20.requestTemperatures();
    float t0 = ds18b20.getTempCByIndex(0);
    float r0 = readSingleTDS(TDS_PIN, t0);
    tds_kalman_estimate = r0; tds_kalman_error_estimate = 1.0f;
    for (int i = 0; i < MA_WINDOW; i++) tds_history[i] = r0;
    ma_index = 0;
    lcd.clear(); lcd.setCursor(0, 0); lcd.print("WARMUP ACTIVE");
    lcd.setCursor(0, 1); lcd.print("Stabilizing...");
}

void checkWarmupStatus() {
    if (warmup_complete) return;
    unsigned long elapsed = millis() - warmup_start_time;
    if (!initial_values_captured && elapsed > 5000UL) {
        initial_tds_value = tdsValue; initial_ph_value = phValue;
        initial_water_temp = waterTemp; initial_values_captured = true;
    }
    if (elapsed >= WARMUP_DURATION) {
        warmup_complete = true; fillingActive = false;
        bothLowStartMs = 0; fillingStartMs = 0;
        tds_kalman_estimate = tdsValue;
        for (int i = 0; i < MA_WINDOW; i++) tds_history[i] = tdsValue;
        waterControlState = STATE_IDLE; emergencyCycleCount = 0; systemLockout = false;
        resetNotifTrackers();
        Serial.println("=== WARMUP COMPLETE ===");
        lcd.clear(); lcd.setCursor(0, 0); lcd.print("WARMUP COMPLETE!");
        lcd.setCursor(0, 1); lcd.print("System ready"); delay(2000);
    }
}

void displayWarmupScreen() {
    static int pg = 0;
    unsigned long elapsed   = millis() - warmup_start_time;
    unsigned long remaining = WARMUP_DURATION - elapsed;
    int pct = (int)((float)elapsed / WARMUP_DURATION * 100.0f);
    if (pg == 0) {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("WARMUP ACTIVE");
        lcd.setCursor(0, 1); lcd.print("Sisa: "); lcd.print(remaining / 1000); lcd.print("s");
        lcd.setCursor(0, 2); lcd.print("TDS: "); lcd.print(tdsValue, 0); lcd.print(" ppm");
        lcd.setCursor(0, 3); lcd.print("pH: "); lcd.print(phValue, 1);
    } else {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("Stabilizing...");
        lcd.setCursor(0, 1); lcd.print("Kalman: "); lcd.print(tds_kalman_estimate, 0); lcd.print(" ppm");
        lcd.setCursor(0, 2); lcd.print("Progress: "); lcd.print(pct); lcd.print("%");
        lcd.setCursor(0, 3); lcd.print("Threshold: "); lcd.print(thresholdLoaded ? "DB" : "DEF");
    }
    pg = (pg + 1) % 2;
}

void readAllSensors() {
    unsigned long now = millis();
    airTemp   = dht.readTemperature() + DHT_TEMP_OFFSET;
    humidity  = dht.readHumidity()    + DHT_HUM_OFFSET;
    ds18b20.requestTemperatures();
    waterTemp = ds18b20.getTempCByIndex(0) + WATER_TEMP_OFFSET;
    phValue   = warmup_complete ? readPHStable(8, 50) : readPHStable(5, 30);
    if (now - previousTDSRead >= TDS_READ_INTERVAL) {
        previousTDSRead = now;
        tdsValue = warmup_complete
                   ? readTDSStable(TDS_PIN, waterTemp, 10, 15)
                   : readTDSStable(TDS_PIN, waterTemp,  5, 10);
    }
    waterLevel = g_highSwitch ? 1.0f : (g_lowSwitch ? 0.5f : 0.0f);
}

void callback(char* topic, byte* payload, unsigned int length) {
    char msg[256];
    if (length >= sizeof(msg)) length = sizeof(msg) - 1;
    memcpy(msg, payload, length); msg[length] = '\0';
    String msgStr = String(msg); String topicStr = String(topic);

    if (topicStr == MQTT_TOPIC_RESET_EMERGENCY) {
        if (msgStr == "RESET") tryResetEmergency(true);
        return;
    }
    if (topicStr == MQTT_TOPIC_MANUAL_OVERRIDE) {
        if (msgStr == "ENABLE") {
            manualOverride = true; fillingStartMs = 0; bothLowStartMs = 0;
        } else if (msgStr == "DISABLE") {
            manualOverride = false; waterControlState = STATE_IDLE;
            fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
        }
        return;
    }
    if (topicStr == "hydroponics/relay/2") {
        if (offlineModeActive) return;
        if ((emergencyStop || switchLogicError || systemLockout) && msgStr == "ON") return;
        safeSetSSR(msgStr == "ON");
        return;
    }
}

void reconnectMQTT() {
    if (mqttClient.connected()) return;
    static unsigned long lastAttempt = 0; static int retryCount = 0;
    unsigned long now = millis();
    unsigned long retryInterval = min(5000UL + (unsigned long)(retryCount / 5) * 5000UL, 30000UL);
    if (now - lastAttempt < retryInterval) return;
    lastAttempt = now; retryCount++;
    String clientId = "ESP32Hydro_" + String((uint32_t)ESP.getEfuseMac(), HEX);
    if (mqttClient.connect(clientId.c_str(), mqtt_user, mqtt_pass)) {
        mqttClient.subscribe("hydroponics/relay/#");
        mqttClient.subscribe(MQTT_TOPIC_RESET_EMERGENCY);
        mqttClient.subscribe(MQTT_TOPIC_MANUAL_OVERRIDE);
        retryCount = 0; Serial.println("MQTT OK");
    }
}

void publishSSRState(bool state) {
    if (!mqttClient.connected()) return;
    StaticJsonDocument<200> doc;
    doc["state"] = state ? "ON" : "OFF";
    char tsBuf[25]; DateTime dt = rtc.now();
    snprintf(tsBuf, sizeof(tsBuf), "%04d-%02d-%02dT%02d:%02d:%02d",
        dt.year(), dt.month(), dt.day(), dt.hour(), dt.minute(), dt.second());
    doc["timestamp"] = tsBuf;
    char buf[200]; serializeJson(doc, buf);
    mqttClient.publish("hydroponics/relay/2/state", buf, false);
}

void turnOffSSR() {
    digitalWrite(SSR_SOLENOID, LOW);
    ssrActive = false; fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
}

void setupSSRPin() {
    pinMode(SSR_SOLENOID, OUTPUT);
    digitalWrite(SSR_SOLENOID, LOW); delay(50);
}

void updateLCD() {
    static int pg = 0;
    if (pg == 0) {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("Udara : ");
        if (isnan(airTemp)) lcd.print("Err");
        else { lcd.print(airTemp, 1); lcd.print((char)223); lcd.print("C"); }
        lcd.setCursor(0, 1); lcd.print("Lembap: ");
        if (isnan(humidity)) lcd.print("Err");
        else { lcd.print(humidity, 1); lcd.print("%"); }
        lcd.setCursor(0, 2); lcd.print("Air   : ");
        if (waterTemp < -55.0f || waterTemp > 125.0f) lcd.print("Err");
        else { lcd.print(waterTemp, 1); lcd.print((char)223); lcd.print("C"); }
        lcd.setCursor(0, 3); lcd.print("pH    : "); lcd.print(phValue, 1);
    } else {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("TDS: "); lcd.print(tdsValue, 0); lcd.print(" ppm");
        lcd.setCursor(0, 1);
        lcd.print("Sw-:"); lcd.print(g_lowSwitch ? "ADA  " : "KURANG");
        lcd.print(" Sw+:"); lcd.print(g_highSwitch ? "ON" : "OF");
        lcd.setCursor(0, 2);
        if      (systemLockout)    lcd.print("SSR : LOCKED!!! ");
        else if (switchLogicError) lcd.print("SSR : ERR-FROZEN");
        else if (emergencyStop)    lcd.print("SSR : EMERGENCY ");
        else if (offlineModeActive)lcd.print("SSR : OFFLINE-OF");
        else if (manualOverride)   { lcd.print("SSR : "); lcd.print(ssrActive ? "MANUAL-ON " : "MANUAL-OFF"); }
        else                       { lcd.print("SSR : "); lcd.print(ssrActive ? "ON  " : "OFF "); }
        lcd.setCursor(0, 3); lcd.print("State: ");
        if      (offlineModeActive) lcd.print("OFFLINE ");
        else if (manualOverride)    lcd.print("MANUAL  ");
        else switch (waterControlState) {
            case STATE_IDLE:      lcd.print("IDLE    "); break;
            case STATE_FILLING:   lcd.print("FILLING "); break;
            case STATE_FULL:      lcd.print("FULL    "); break;
            case STATE_BETWEEN:   lcd.print("SEDANG  "); break;
            case STATE_EMERGENCY: lcd.print("EMERGCY!"); break;
        }
    }
    pg = (pg + 1) % 2;
}

void publishSensorData(const char* topic, float value, const char* unit) {
    if (!mqttClient.connected()) return;
    StaticJsonDocument<200> doc;
    doc["value"] = value; doc["unit"] = unit;
    char tsBuf[25]; DateTime dt = rtc.now();
    snprintf(tsBuf, sizeof(tsBuf), "%04d-%02d-%02dT%02d:%02d:%02d",
        dt.year(), dt.month(), dt.day(), dt.hour(), dt.minute(), dt.second());
    doc["timestamp"] = tsBuf;
    char buf[256]; serializeJson(doc, buf);
    mqttClient.publish(topic, buf, false);
}

void checkFilterStatus() {
    static unsigned long last = 0;
    if (millis() - last < 10000UL) return;
    last = millis();
    Serial.printf("[FILTER] Kalman=%.1f err=%.4f\n", tds_kalman_estimate, tds_kalman_error_estimate);
}

void syncRTCWithNTP() {
    lcd.clear(); lcd.setCursor(0, 0); lcd.print("Syncing time...");
    timeClient.begin(); timeClient.setTimeOffset(8 * 3600);
    bool ok = false; int att = 0;
    while (!ok && att < 20) { timeClient.forceUpdate(); ok = timeClient.isTimeSet(); if (!ok) { delay(1000); att++; } }
    if (ok) {
        time_t t = (time_t)timeClient.getEpochTime();
        struct tm* ti = localtime(&t);
        rtc.adjust(DateTime(ti->tm_year + 1900, ti->tm_mon + 1, ti->tm_mday,
                            ti->tm_hour, ti->tm_min, ti->tm_sec));
        lcd.clear(); lcd.setCursor(0, 0); lcd.print("Time synced OK"); delay(2000);
    } else {
        lcd.clear(); lcd.setCursor(0, 0); lcd.print("NTP sync failed"); delay(2000);
    }
    timeClient.end();
}

void reconnectWiFi() {
    if (WiFi.status() == WL_CONNECTED) return;
    handleOfflineMode();
    WiFi.disconnect(); WiFi.reconnect();
    int att = 0;
    while (WiFi.status() != WL_CONNECTED && att < 10) { delay(1000); att++; }
    Serial.println(WiFi.status() == WL_CONNECTED ? "WiFi OK" : "WiFi FAIL");
}


// ==============================================================================
// FUNGSI PENGIRIMAN FULL REST API (POST /api/save-sensor)
// Mengirimkan telemetri dan menerima instruksi kontrol valve sekaligus!
// ==============================================================================
void sendSensorDataViaREST() {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    String url = String(website_base_url) + "/api/save-sensor";

    if (url.startsWith("https://")) {
        WiFiClientSecure clientSecure;
        clientSecure.setInsecure();
        http.begin(clientSecure, url);
    } else {
        WiFiClient client;
        http.begin(client, url);
    }

    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", website_api_key);
    http.setTimeout(4000);

    // Tentukan status water level string
    String waterLvlStr = "Kurang";
    if (systemLockout) {
        waterLvlStr = "LOCKOUT";
    } else if (switchLogicError) {
        waterLvlStr = "ERROR";
    } else if (g_highSwitch && g_lowSwitch) {
        waterLvlStr = "Penuh";
    } else if (g_lowSwitch && !g_highSwitch) {
        waterLvlStr = "Sedang";
    } else {
        waterLvlStr = "Rendah";
    }

    StaticJsonDocument<512> doc;
    if (!isnan(waterTemp) && waterTemp > -55.0f && waterTemp < 125.0f) {
        doc["waterTemp"] = roundf(waterTemp * 10.0f) / 10.0f;
    }
    doc["ph"]          = roundf(phValue  * 10.0f) / 10.0f;
    doc["tds"]         = roundf(tdsValue * 10.0f) / 10.0f;
    if (!isnan(airTemp)) {
        doc["airTemp"] = roundf(airTemp * 10.0f) / 10.0f;
    }
    if (!isnan(humidity)) {
        doc["humidity"] = roundf(humidity * 10.0f) / 10.0f;
    }
    doc["water_level"]    = waterLvlStr;
    doc["solenoid_state"] = ssrActive ? "ON" : "OFF";

    String requestBody;
    serializeJson(doc, requestBody);

    int httpCode = http.POST(requestBody);

    if (httpCode == 200) {
        String response = http.getString();
        StaticJsonDocument<512> respDoc;
        DeserializationError err = deserializeJson(respDoc, response);

        if (!err) {
            const char* cmd = respDoc["command"] | "AUTO";
            const char* sMode = respDoc["mode"] | "AUTO";

            // Update Mode
            if (strcmp(sMode, "MANUAL") == 0) {
                manualOverride = true;
            } else if (strcmp(sMode, "AUTO") == 0) {
                manualOverride = false;
            }

            // Eksekusi Perintah Kontrol dari Web
            if (strcmp(cmd, "FORCE_FILL") == 0) {
                Serial.println("[REST COMMAND] Diterima perintah: FORCE_FILL");
                manualOverride = true;
                safeSetSSR(true);
            } else if (strcmp(cmd, "FORCE_STOP") == 0) {
                Serial.println("[REST COMMAND] Diterima perintah: FORCE_STOP");
                manualOverride = true;
                safeSetSSR(false);
            }
        }
    } else {
        Serial.printf("[REST] Gagal kirim sensor, HTTP code: %d
", httpCode);
    }
    http.end();
}

void setup() {
    Serial.begin(115200); delay(2000);
    Serial.println("\n=== HYDROPONICS v4.0 - ANTI-SPAM + DB THRESHOLD ===");

    Wire.begin(SDA_PIN, SCL_PIN); Wire.setClock(100000);
    Wire1.begin(SDA2_PIN, SCL2_PIN); Wire1.setClock(100000);

    lcd.begin(20, 4); lcd.clear(); lcd.backlight();
    lcd.setCursor(0, 0); lcd.print("SYSTEM STARTING...");

    setupSSRPin(); dht.begin(); ds18b20.begin();

    if (!rtc.begin(&Wire1)) { Serial.println("RTC not found!"); }
    else if (rtc.lostPower()) { rtc.adjust(DateTime(F(__DATE__), F(__TIME__))); }

    pinMode(WATER_SWITCH_LOW_PIN,  INPUT_PULLUP);
    pinMode(WATER_SWITCH_HIGH_PIN, INPUT_PULLUP);

    // Reset semua state
    switchLogicError = false; errorStartTime = 0;
    emergencyStop = false; emergencyStartTime = 0;
    emergencyCycleCount = 0; systemLockout = false; lastEmergencyWarnMs = 0;
    ssrActive = false; fillingActive = false; manualOverride = false;
    offlineModeActive = false; offlineLCDShown = false; offlineStartTime = 0;
    waterControlState = STATE_IDLE; bothLowStartMs = 0; fillingStartMs = 0;
    thresholdLoaded = false; lastThresholdFetch = 0;

    // Reset semua cooldown
    cooldownWaterLevel = 0; cooldownSolenoid = 0; cooldownPh = 0;
    cooldownTds = 0; cooldownSuhuAir = 0; cooldownAirTemp = 0;
    cooldownHumidity = 0; cooldownEmergency = 0;
    cooldownSwitchError = 0; cooldownLockout = 0;

    resetNotifTrackers();
    updateGlobalSwitchState();

    if (!g_lowSwitch && g_highSwitch) {
        Serial.println("BOOT WARNING: Switch logic error!");
        switchLogicError = true; errorStartTime = millis();
    }

    initializeWarmup();

    // Baca URL Server & API Key yang tersimpan di NVS Flash
    preferences.begin("harvest_cfg", false);
    String savedUrl = preferences.getString("server_url", "https://harvesthouse.biz.id");
    String savedKey = preferences.getString("api_key", "HARVEST123");
    strncpy(website_base_url, savedUrl.c_str(), sizeof(website_base_url) - 1);
    strncpy(website_api_key, savedKey.c_str(), sizeof(website_api_key) - 1);

    lcd.clear(); 
    lcd.setCursor(0, 0); 
    lcd.print("Menghubungkan WiFi..");

    WiFiManager wm;
    wm.setAPCallback(configModeCallback);
    wm.setConfigPortalTimeout(180); // 3 Menit timeout jika tidak ada konfigurasi dari HP

    // Kolom input tambahan di halaman WiFi HP
    WiFiManagerParameter custom_server("server_url", "Server URL (misal https://xxx.lhr.life)", website_base_url, 128);
    WiFiManagerParameter custom_key("api_key", "API Key Website", website_api_key, 32);
    wm.addParameter(&custom_server);
    wm.addParameter(&custom_key);

    // AutoConnect: jika WiFi lama ketemu, langsung connect.
    // Jika tidak ketemu, otomatis buat Access Point "HarvestHouse-Setup" (Password: kebun2026)
    bool wifiConnected = wm.autoConnect("HarvestHouse-Setup", "kebun2026");

    if (wifiConnected) {
        // Simpan setting server baru jika diisi dari HP
        if (strlen(custom_server.getValue()) > 0) {
            strncpy(website_base_url, custom_server.getValue(), sizeof(website_base_url) - 1);
            preferences.putString("server_url", String(website_base_url));
        }
        if (strlen(custom_key.getValue()) > 0) {
            strncpy(website_api_key, custom_key.getValue(), sizeof(website_api_key) - 1);
            preferences.putString("api_key", String(website_api_key));
        }
        preferences.end();
        Serial.printf("[CONFIG] Server Base URL: %s
", website_base_url);
    } else {
        preferences.end();
        Serial.println("[WIFI] Timeout portal atau gagal terhubung ke WiFi!");
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("WiFi OK");
        lcd.setCursor(0, 1); lcd.print(WiFi.localIP()); delay(1500);
        syncRTCWithNTP();
        espClient.setInsecure();
        mqttClient.setBufferSize(1024);
        mqttClient.setServer(mqtt_server, mqtt_port);
        mqttClient.setCallback(callback);
        reconnectMQTT();

        lcd.clear(); lcd.setCursor(0, 0); lcd.print("Load threshold..");
        fetchThresholdsFromWebsite();
        lcd.setCursor(0, 1); lcd.print(thresholdLoaded ? "Threshold: DB OK" : "Threshold: DEF  ");
        delay(1500);
    } else {
        Serial.println("WiFi FAIL, mode offline");
        offlineModeActive = true; offlineLCDShown = false; offlineStartTime = millis();
        lcd.clear(); lcd.setCursor(0, 0); lcd.print("WiFi Gagal");
        lcd.setCursor(0, 1); lcd.print("Mode: OFFLINE");
        lcd.setCursor(0, 2); lcd.print("Threshold: DEFAULT"); delay(2000);
    }

    unsigned long t0 = millis();
    previousSensorRead = t0; previousPublish = t0; previousLCDUpdate = t0;
    previousMQTTLoop = t0; previousTDSRead = t0;
    lastSafetyCheck = t0; lastKalmanResetTime = t0;

    Serial.println("Setup selesai v4.0");
}

void loop() {
    unsigned long now = millis();

    updateGlobalSwitchState();

    if (now - lastSafetyCheck >= SAFETY_CHECK_INTERVAL) {
        lastSafetyCheck = now;
        checkSafetySystems();
    }

    if (emergencyStop || systemLockout) {
        tryResetEmergency(false);
    }

    if (!warmup_complete) {
        checkWarmupStatus();
        if (ssrActive) safeSetSSR(false);
        // MQTT Loop removed for Full REST API
        // MQTT Reconnect removed
        reconnectWiFi();
        if (now - previousSensorRead >= 1000UL) { previousSensorRead = now; readAllSensors(); }
        if (now - previousLCDUpdate  >= 1000UL) { previousLCDUpdate  = now; displayWarmupScreen(); }
        return;
    }

    // MQTT Loop removed for Full REST API

    if (!isOnline()) {
        handleOfflineMode(); reconnectWiFi();
        
        if (now - previousSensorRead >= SENSOR_READ_INTERVAL) { previousSensorRead = now; readAllSensors(); }
        if (now - previousLCDUpdate  >= LCD_UPDATE_INTERVAL)  { previousLCDUpdate  = now; updateLCD(); }
        return;
    }

    handleBackOnline();

    // Fetch threshold dari DB setiap 10 menit
    if (now - lastThresholdFetch >= THRESHOLD_FETCH_INTERVAL) {
        fetchThresholdsFromWebsite();
    }

    if (now - lastKalmanResetTime >= KALMAN_RESET_INTERVAL) resetKalmanFilter();
    checkFilterStatus();

    if (now - previousSensorRead >= SENSOR_READ_INTERVAL) { previousSensorRead = now; readAllSensors(); }

    updateWaterControlState();
    controlWaterSystem();
    logWaterControlDebug();

    // Cek dan kirim notifikasi (dengan dua lapis anti-spam)
    checkAndSendNotifications();

    if (now - previousPublish >= PUBLISH_INTERVAL) {
        previousPublish = now;

        if (!isnan(waterTemp) && waterTemp > -55.0f && waterTemp < 125.0f)
            publishSensorData(MQTT_TOPIC_SUHU_AIR, roundf(waterTemp * 10.0f) / 10.0f, "C");
        publishSensorData(MQTT_TOPIC_PH,  roundf(phValue  * 10.0f) / 10.0f, "pH");
        publishSensorData(MQTT_TOPIC_TDS, roundf(tdsValue * 10.0f) / 10.0f, "ppm");
        if (!isnan(airTemp))
            publishSensorData(MQTT_TOPIC_SUHU_UDARA, roundf(airTemp * 10.0f) / 10.0f, "C");
        if (!isnan(humidity))
            publishSensorData(MQTT_TOPIC_KELEMBAPAN, roundf(humidity * 10.0f) / 10.0f, "%");

        if (mqttClient.connected()) {
            if      (systemLockout)               mqttClient.publish(MQTT_TOPIC_WATER_LEVEL, "{\"status\":\"LOCKOUT\",\"error\":\"max_emergency_cycles\"}", true);
            else if (switchLogicError)            mqttClient.publish(MQTT_TOPIC_WATER_LEVEL, "{\"status\":\"ERROR\",\"error\":\"switch_logic_invalid\"}", true);
            else if (g_highSwitch && g_lowSwitch) mqttClient.publish(MQTT_TOPIC_WATER_LEVEL, "{\"status\":\"Penuh\",\"low_sw\":1,\"high_sw\":1}", false);
            else if (g_lowSwitch && !g_highSwitch)mqttClient.publish(MQTT_TOPIC_WATER_LEVEL, "{\"status\":\"Sedang\",\"low_sw\":1,\"high_sw\":0}", false);
            else                                  mqttClient.publish(MQTT_TOPIC_WATER_LEVEL, "{\"status\":\"Kurang\",\"low_sw\":0,\"high_sw\":0}", true);
        }
    }

    if (now - previousLCDUpdate >= LCD_UPDATE_INTERVAL) { previousLCDUpdate = now; updateLCD(); }
}