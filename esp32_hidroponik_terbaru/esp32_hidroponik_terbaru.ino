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
#include "DFRobot_PH.h"    // Library: "DFRobot_PH" — pH DFRobot Gravity SEN0161-V2
#include <EEPROM.h>        // Penyimpanan kalibrasi pH DFRobot (wajib di ESP32)


// ==================================================================
// ====== KONFIGURASI ALAT - UBAH DI SINI SAAT SETTING ==============
// ==================================================================
// 1) WIFI (jaringan 2.4 GHz)
const char* ssid     = "HarvestHouse";   // <- nama WiFi
const char* password = "house2025";       // <- password WiFi

// 2) SERVER WEB - API key harus SAMA dengan ESP32_API_KEY di .env website!
const char* website_api_key       = "muhammadbudisatria900";  // SAMA dengan ESP32_API_KEY di .env website
const char* website_notif_url     = "https://harvesthouse.biz.id/api/notif-hardware";
const char* website_threshold_url = "https://harvesthouse.biz.id/api/get-thresholds";
// ==================================================================


WiFiUDP   ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");

#define SSR_SOLENOID          25
// Relay 5V (BUKAN SSR): trigger active-LOW -> IN LOW = relay ON (koil nyala)
#define RELAY_ON_LEVEL   LOW
#define RELAY_OFF_LEVEL  HIGH
#define BUZZER_PIN            26    // Buzzer aktif (alarm & feedback suara)
// Float switch magnetik batang (2 pelampung, 6 kabel) — dipasang KEPALA DRAT DI BAWAH:
//   pelampung PANJANG (dekat kepala, posisi bawah) = level minimum -> GPIO14
//   pelampung PENDEK  (ujung batang, posisi atas)  = level penuh   -> GPIO27
//
//   MAPPING FINAL (verifikasi 2026-10-10, dari test_identifikasi_switch):
//   GPIO14 = level MINIMUM (bawah)
//   GPIO27 = level PENUH   (atas)
//
//   Kedua switch sama: terendam/mengapung = pin HIGH, kering = pin LOW.
//   Logika: kosong = 14 LOW & 27 LOW | sedang = 14 HIGH & 27 LOW | penuh = 14 HIGH & 27 HIGH.
//   Catatan lama di file ini (baris 41-45 & 47-52 versi lama) menulis dua versi
//   bertentangan — sudah diseragamkan dengan hasil verifikasi di atas.
#define WATER_SWITCH_LOW_PIN  14   // level MINIMUM (bawah) - dipastikan 2026-10-10
#define WATER_SWITCH_HIGH_PIN 27   // level PENUH   (atas)  - dipastikan 2026-10-10
#define DHTPIN                4
#define DHTTYPE               DHT22

#define DHT_TEMP_OFFSET       1.4f 
#define DHT_HUM_OFFSET       -18.4f

#define ONE_WIRE_BUS          18
#define SDA_PIN               21
#define SCL_PIN               22
#define SDA2_PIN              16
#define SCL2_PIN              17
#define PH_PIN                33    // Sensor pH DFRobot Gravity (ADC1)
#define TDS_PIN               34
#define MAX_FILLING_RUNTIME        420000UL   // 7 menit: solenoid mati otomatis jika belum penuh

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
unsigned long impossibleStartMs = 0;   // mulai grace combo mustahil (0 = tidak aktif)
#define SWITCH_ERROR_LOCKOUT_MS 5000UL
#define SWITCH_ERROR_GRACE_MS   1000UL   // combo mustahil harus stabil 1 dtk baru jadi ERROR

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

// pH DFRobot: kalibrasi via PUSH BUTTON (3 titik: buffer 4.01, 6.86, 9.18 @25C)
// atau via Serial Monitor (ENTERPH / CALPH / EXITPH).
// pH_benar = phCalSlope * pH_terukur + phCalOffset   (tersimpan di EEPROM 36 & 40)
// Nilai bawaan = hasil uji lab 2 titik (buffer 4.01 & 6.86 @25C).
#define PH_CAL_SLOPE_DEFAULT   1.009338f
#define PH_CAL_OFFSET_DEFAULT  (-1.115993f)
float phCalSlope  = PH_CAL_SLOPE_DEFAULT;
float phCalOffset = PH_CAL_OFFSET_DEFAULT;
DFRobot_PH ph;   // Sensor pH DFRobot Gravity (SEN0161-V2)

// ===== KALIBRASI TDS (1 titik) via Serial Monitor =====
// Perintah (Serial 115200, Newline):
//   TDSCAL <ppm>  : kalibrasi saat probe di larutan standar, mis. "TDSCAL 500"
//   TDSRESET      : kembalikan faktor ke default
//   TDSSTATUS     : tampilkan faktor kalibrasi TDS saat ini
// Faktor disimpan di EEPROM alamat 32 (di luar area DFRobot_PH 0..31)
#define TDS_CAL_DEFAULT      1.252f
#define TDS_CAL_EEPROM_ADDR  32
float tdsCalFactor = TDS_CAL_DEFAULT;
#define WATER_TEMP_OFFSET       0.4f

// ===== PUSH BUTTON KALIBRASI + BUZZER (kalibrasi lapangan tanpa Serial) =====
// Tombol NO antara GPIO32 dan GND (internal pullup). Buzzer memberi aba-aba:
//   1 bip saat ditahan = zona pH   (lepas sekarang = kalibrasi pH)
//   2 bip saat ditahan = zona TDS  (lepas sekarang = kalibrasi TDS)
// Saat tombol DILEPAS:
//   pendek < 1.5 dtk     = tes buzzer
//   1.5 - 4.5 dtk        = kalibrasi pH 3 titik (4.01 -> 6.86 -> 9.18)
//   > 4.5 dtk            = kalibrasi TDS 1 titik (larutan TDS_STANDARD_PPM)
// Di dalam mode: pendek = simpan titik | tahan 2 dtk = batal | idle 3 menit = batal
#define BUTTON_PIN         32
#define BTN_SHORT_MS       1500UL
#define BTN_CAL_TDS_MS     4500UL
#define BTN_BATAL_MS       2000UL
#define KAL_TIMEOUT_MS     180000UL
#define TDS_STANDARD_PPM   1382.0f    // ppm larutan standar TDS (ubah di sini jika berbeda)
#define PH_BUFFER_1        4.01f
#define PH_BUFFER_2        6.86f
#define PH_BUFFER_3        9.18f
#define PH_CAL_SLOPE_ADDR  36
#define PH_CAL_OFFSET_ADDR 40

enum KalMode : uint8_t { KAL_OFF, KAL_PH, KAL_TDS };
KalMode       kalMode        = KAL_OFF;
uint8_t       kalLangkah     = 0;
float         kalTitikV[3];
bool          btnTekan       = false;
unsigned long btnSejak       = 0;
uint8_t       btnTanda       = 0;
unsigned long kalAktifSejak  = 0;
unsigned long previousKalLCD = 0;

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


float waterTemp  = 0;
float airTemp    = 0;
float humidity   = 0;
float phValue    = 0;
float tdsValue   = 0;
float waterLevel = 0;

bool  ssrActive  = false;

unsigned long previousSensorRead = 0;
unsigned long lastDhtRead        = 0;   // jeda min 2 dtk antar bacaan DHT22
unsigned long previousLCDUpdate  = 0;
unsigned long previousTDSRead    = 0;

const unsigned long SENSOR_READ_INTERVAL = 3000UL;
const unsigned long LCD_UPDATE_INTERVAL  = 3000UL;
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
void syncRTCFromServerTime(const String& isoUtc);
void reconnectWiFi();
void setupSSRPin();
void safeSetSSR(bool turnOn);
float readPH();
float readTDSStable(int tdsPin, float wTemp, int samples, int delayMs);
void updateLCD();
void turnOffSSR();
void readAllSensors();
float readPHStable(int samples, int delayMs);
float kalmanFilterTDS(float measurement);
float readSingleTDS(int tdsPin, float wTemp);
void handleTDSCalibration(const char* cmd);
void saveTdsCal();
void loadTdsCal();
void savePhCal();
void loadPhCal();
void cekTombolKalibrasi();
void masukModeKalibrasi(KalMode mode);
void batalModeKalibrasi();
void konfirmasiLangkahKalibrasi();
void tampilLayarKalibrasi();
void instruksiLangkahPH();
float readPHMeasuredAvg();
void beepSimpan();
void beepBatal();
float movingAverageFilter(float new_value);
void initializeWarmup();
void checkWarmupStatus();
void displayWarmupScreen();
void checkSafetySystems();
void tryResetEmergency();
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
void setupBuzzer();
void beepClick();
void beepSuccess();
void beepAlarm();
void beepEmergency();

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
    if (type != "emergency") beepAlarm();   // darurat sudah punya siren sendiri

    HTTPClient http;
    WiFiClientSecure clientSecure;
    clientSecure.setInsecure();

    http.begin(clientSecure, website_notif_url);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(8000);

    StaticJsonDocument<512> doc;   // 512 cukup untuk api_key+type+value+message
    doc["api_key"] = website_api_key;
    doc["type"]    = type;
    doc["value"]   = value;
    if (customMsg.length() > 0) {
        doc["message"] = customMsg;
    }

    char body[512];
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
    // Abaikan nilai sampah (probe menggantung / EEPROM belum dikalibrasi):
    // pH valid hanya 0-14, nan = sensor tidak siap
    bool phValid = (!isnan(phValue) && phValue >= 0.0f && phValue <= 14.0f);
    bool phAbnormal = phValid && (phValue < th_ph.minVal || phValue > th_ph.maxVal);
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
            "Terlalu banyak siklus emergency. Sistem terkunci sampai direset manual.",
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

    String thresholdUrl = String(website_threshold_url) + "?api_key=" + website_api_key;
    http.begin(clientSecure, thresholdUrl);
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
        digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL);
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

    return (lastStable == HIGH);  // terendam = pin HIGH (kedua switch sama)
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

    return (lastStable == HIGH);  // terendam = pin HIGH (kedua switch sama)
}

bool validateSwitchLogic(bool lowFull, bool highFull) {
    bool impossible = (!lowFull && highFull);

    if (impossible) {
        // Grace: combo mustahil harus STABIL dulu baru dinyatakan ERROR.
        // Transien (noise koil relay / ombak saat mengisi) di bawah grace diabaikan.
        if (impossibleStartMs == 0) { impossibleStartMs = millis(); }
        if (millis() - impossibleStartMs < SWITCH_ERROR_GRACE_MS) { return true; }
        if (!switchLogicError) {
            switchLogicError = true;
            errorStartTime   = millis();
            bothLowStartMs   = 0;
            fillingStartMs   = 0;
            Serial.println("!!! SWITCH LOGIC ERROR: bawah=KURANG tapi atas=PENUH!");
            beepAlarm();
            if (ssrActive) {
                digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL);
                ssrActive = false;
               
            }
        }
        if (millis() - errorStartTime > MAX_SWITCH_ERROR_DURATION) {
            switchLogicError   = false;
            waterControlState  = STATE_EMERGENCY;
            emergencyStop      = true;
            emergencyStartTime = millis();
            beepEmergency();
        }
        return false;
    }

    impossibleStartMs = 0;   // kombinasi valid -> reset grace

    if (switchLogicError) {
        unsigned long cd = millis() - errorStartTime;
        if (cd < SWITCH_ERROR_LOCKOUT_MS) {
            if (ssrActive) { digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL); ssrActive = false; }
            return false;
        }
        switchLogicError = false;
        Serial.println("Switch logic OK kembali.");
    }

    return true;
}

void tryResetEmergency() {
    if (!emergencyStop) return;

    unsigned long now     = millis();
    unsigned long elapsed = now - emergencyStartTime;

    if (elapsed < EMERGENCY_COOLDOWN_MS) return;

    if (systemLockout) return;

    if (g_highSwitch) {
        emergencyStop = false; emergencyStartTime = 0;
        emergencyCycleCount = 0; fillingActive = false;
        bothLowStartMs = 0; fillingStartMs = 0;
        waterControlState = STATE_IDLE;
        lastNotifEmergency = false;
        Serial.println("[EMERGENCY] Reset - air penuh.");
        return;
    }

    emergencyCycleCount++;
    Serial.printf("[EMERGENCY] Cycle %u/%u\n", emergencyCycleCount, MAX_EMERGENCY_CYCLES);

    if (emergencyCycleCount >= MAX_EMERGENCY_CYCLES) {
        systemLockout = true;
        Serial.println("[EMERGENCY] MAX CYCLES, SYSTEM LOCKOUT!");
        beepEmergency();
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
        if (waterControlState != STATE_FULL) { Serial.println("STATE -> FULL"); beepSuccess(); }
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
        if (ssrActive) { digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL); ssrActive = false; fillingStartMs = 0; }
        return;
    }

    if (manualOverride) {
        if ((emergencyStop || systemLockout || switchLogicError) && ssrActive) {
            digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL); ssrActive = false; fillingStartMs = 0;
        }
        return;
    }

    if (switchLogicError || systemLockout) {
        if (ssrActive) { digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL); ssrActive = false; fillingStartMs = 0; }
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
    // STATUS = hasil debounce (stabil 150 ms), GPIO = bacaan pin langsung.
    // Keduanya BOLEH berbeda sejenak ketika pin baru berubah — itu tanda
    // transisi yang sedang diuji, bukan pembacaan keliru. Tanpa penanda "~"
    // di bawah, baris "KURANG (GPIO14=HIGH)" terbaca sebagai kontradiksi
    // padahal cuma state yang belum ter-update.
    int pinLow  = digitalRead(WATER_SWITCH_LOW_PIN);
    int pinHigh = digitalRead(WATER_SWITCH_HIGH_PIN);
    Serial.printf("Switch BAWAH  : %s (GPIO%d=%s%s)\n",
        g_lowSwitch ? "ADA" : "KURANG", WATER_SWITCH_LOW_PIN,
        pinLow == HIGH ? "HIGH" : "LOW",
        ((pinLow == HIGH) != (bool) g_lowSwitch) ? " ~baru berubah" : "");
    Serial.printf("Switch ATAS   : %s (GPIO%d=%s%s)\n",
        g_highSwitch ? "PENUH" : "BELUM", WATER_SWITCH_HIGH_PIN,
        pinHigh == HIGH ? "HIGH" : "LOW",
        ((pinHigh == HIGH) != (bool) g_highSwitch) ? " ~baru berubah" : "");
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
            beepEmergency();
            digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL);
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

    digitalWrite(SSR_SOLENOID, turnOn ? RELAY_ON_LEVEL : RELAY_OFF_LEVEL);
    ssrActive = turnOn;
    if (!turnOn) fillingStartMs = 0;
    Serial.printf("RELAY: %s\n", turnOn ? "ON" : "OFF");
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
    // DFRobot pH (SEN0161-V2): ADC -> mV, konversi ke pH + kompensasi suhu air
    float voltage = (float)raw * 3300.0f / 4095.0f;
    float phMeasured = ph.readPH(voltage, waterTemp);
    return phCalSlope * phMeasured + phCalOffset;   // koreksi kalibrasi lab (EEPROM)
}

float readPHStable(int samples, int delayMs) {
    float sum = 0;
    for (int i = 0; i < samples; i++) {
        sum += readPH();
        unsigned long t0 = millis();
        while (millis() - t0 < (unsigned long)delayMs) { delay(5); }
    }
    return sum / samples;
}

// Kalibrasi pH DFRobot via Serial Monitor (ENTERPH / CALPH / EXITPH)
void handlePHCalibration() {
    static char cmdBuf[16];
    static byte cmdIdx = 0;
    while (Serial.available() > 0) {
        char c = Serial.read();
        if (c == '\n' || cmdIdx >= sizeof(cmdBuf) - 1) {
            cmdBuf[cmdIdx] = '\0';
            if (cmdIdx > 0) {
                if (strstr(cmdBuf, "TDSCAL") != NULL) {
                    handleTDSCalibration(cmdBuf);
                } else if (strstr(cmdBuf, "TDSRESET") != NULL) {
                    tdsCalFactor = TDS_CAL_DEFAULT;
                    saveTdsCal();
                    Serial.printf("[TDS] Faktor direset ke %.3f\n", tdsCalFactor);
                } else if (strstr(cmdBuf, "TDSSTATUS") != NULL) {
                    Serial.printf("[TDS] Faktor kalibrasi: %.3f (default %.3f)\n", tdsCalFactor, TDS_CAL_DEFAULT);
                } else {
                    float voltage = (float)analogRead(PH_PIN) * (3300.0f / 4095.0f);
                    ph.calibration(voltage, waterTemp, cmdBuf);
                    if (strstr(cmdBuf, "EXITPH") != NULL) EEPROM.commit();
                }
            }
            cmdIdx = 0;
        } else if (c != '\r') {
            cmdBuf[cmdIdx++] = c;
        }
    }
}

// ===== Kalibrasi TDS 1 titik: baca rata-rata lalu sesuaikan faktor =====
void handleTDSCalibration(const char* cmd) {
    float truePPM = atof(cmd + 6);   // nilai setelah "TDSCAL"
    if (truePPM <= 0.0f || truePPM > 10000.0f) {
        Serial.println("[TDS] Format: TDSCAL <ppm>, mis. TDSCAL 500");
        return;
    }
    float measuredPPM = readTDSStable(TDS_PIN, waterTemp, 10, 50);
    if (measuredPPM < 1.0f) {
        Serial.println("[TDS] Gagal: pembacaan terlalu rendah/aneh - probe kering?");
        return;
    }
    tdsCalFactor = tdsCalFactor * (truePPM / measuredPPM);
    saveTdsCal();
    Serial.printf("[TDS] Terbaca %.1f ppm -> dikoreksi ke %.0f ppm | Faktor baru: %.3f (tersimpan)\n",
                  measuredPPM, truePPM, tdsCalFactor);
}

void saveTdsCal() {
    EEPROM.put(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
    EEPROM.commit();
}

void loadTdsCal() {
    EEPROM.get(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
    if (isnan(tdsCalFactor) || tdsCalFactor < 0.1f || tdsCalFactor > 20.0f) {
        tdsCalFactor = TDS_CAL_DEFAULT;   // EEPROM kosong / belum pernah dikalibrasi
    }
}

// ===== Kalibrasi lab pH (slope/offset) di EEPROM alamat 36 & 40 =====
void savePhCal() {
    EEPROM.put(PH_CAL_SLOPE_ADDR, phCalSlope);
    EEPROM.put(PH_CAL_OFFSET_ADDR, phCalOffset);
    EEPROM.commit();
}

void loadPhCal() {
    EEPROM.get(PH_CAL_SLOPE_ADDR, phCalSlope);
    EEPROM.get(PH_CAL_OFFSET_ADDR, phCalOffset);
    if (isnan(phCalSlope) || isnan(phCalOffset) || fabsf(phCalSlope) > 50.0f || fabsf(phCalOffset) > 50.0f) {
        phCalSlope  = PH_CAL_SLOPE_DEFAULT;   // EEPROM kosong -> pakai hasil uji lab
        phCalOffset = PH_CAL_OFFSET_DEFAULT;
    }
}

// Rata-rata pH "terukur" (SEBELUM koreksi lab) — bahan regresi 3 titik
float readPHMeasuredAvg() {
    float sum = 0;
    for (int i = 0; i < 10; i++) {
        float v = (float)analogRead(PH_PIN) * (3300.0f / 4095.0f);
        sum += ph.readPH(v, waterTemp);
        delay(50);
    }
    return sum / 10.0f;
}

void tampilLayarKalibrasi() {
    lcd.setCursor(0, 2);
    if (kalMode == KAL_PH) {
        float v = (float)analogRead(PH_PIN) * (3300.0f / 4095.0f);
        lcd.print("Ukur: "); lcd.print(ph.readPH(v, waterTemp), 2); lcd.print("      ");
    } else if (kalMode == KAL_TDS) {
        lcd.print("Ukur: "); lcd.print(readSingleTDS(TDS_PIN, waterTemp), 0); lcd.print(" ppm   ");
    }
}

void instruksiLangkahPH() {
    static const char* nama[3] = { "pH 4.01", "pH 6.86", "pH 9.18" };
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("KALIBRASI pH "); lcd.print(kalLangkah + 1); lcd.print("/3");
    lcd.setCursor(0, 1); lcd.print("Celup ke "); lcd.print(nama[kalLangkah]);
    lcd.setCursor(0, 3); lcd.print("Pendek=OK Lama=Batal");
}

void masukModeKalibrasi(KalMode mode) {
    kalMode = mode;
    kalLangkah = 0;
    kalAktifSejak = millis();
    safeSetSSR(false);   // keamanan: probe keluar dari air -> kontrol isi air dihentikan
    if (mode == KAL_PH) {
        for (int i = 0; i < 3; i++) beep(80, 80);
        instruksiLangkahPH();
    } else {
        for (int i = 0; i < 4; i++) beep(60, 60);
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("KALIBRASI TDS");
        lcd.setCursor(0, 1); lcd.print("Celup larutan std");
        lcd.setCursor(0, 2); lcd.print("harus "); lcd.print(TDS_STANDARD_PPM, 0); lcd.print(" ppm");
        lcd.setCursor(0, 3); lcd.print("Pendek=OK Lama=Batal");
    }
    Serial.printf("[KAL] Mode kalibrasi %s aktif\n", mode == KAL_PH ? "pH 3 titik" : "TDS 1 titik");
}

void batalModeKalibrasi() {
    kalMode = KAL_OFF;
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("KALIBRASI BATAL");
    lcd.setCursor(0, 1); lcd.print("Kalibrasi lama tetap");
    beepBatal();
    Serial.println("[KAL] Dibatalkan - kalibrasi lama tetap dipakai");
}

void konfirmasiLangkahKalibrasi() {
    kalAktifSejak = millis();
    if (kalMode == KAL_PH) {
        static const float bufferPH[3] = { PH_BUFFER_1, PH_BUFFER_2, PH_BUFFER_3 };
        kalTitikV[kalLangkah] = readPHMeasuredAvg();
        Serial.printf("[KAL] Titik pH %d: buffer %.2f -> terukur %.3f\n",
                      kalLangkah + 1, bufferPH[kalLangkah], kalTitikV[kalLangkah]);
        beepSuccess();
        kalLangkah++;
        if (kalLangkah < 3) { instruksiLangkahPH(); return; }

        // Regresi linier 3 titik: buffer = slope * terukur + offset
        float sx = 0, sy = 0, sxx = 0, sxy = 0;
        for (int i = 0; i < 3; i++) {
            sx += kalTitikV[i]; sy += bufferPH[i];
            sxx += kalTitikV[i] * kalTitikV[i]; sxy += kalTitikV[i] * bufferPH[i];
        }
        float d = 3.0f * sxx - sx * sx;
        if (fabsf(d) < 1e-6f) {
            kalMode = KAL_OFF;
            lcd.clear(); lcd.setCursor(0, 0); lcd.print("KALIBRASI GAGAL");
            lcd.setCursor(0, 1); lcd.print("3 titik sejajar?");
            beepAlarm();
            Serial.println("[KAL] Gagal: 3 titik membentuk garis degenerate");
            return;
        }
        phCalSlope  = (3.0f * sxy - sx * sy) / d;
        phCalOffset = (sy - phCalSlope * sx) / 3.0f;
        savePhCal();
        kalMode = KAL_OFF;
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("pH TERSIMPAN!");
        lcd.setCursor(0, 1); lcd.print("slope = "); lcd.print(phCalSlope, 4);
        lcd.setCursor(0, 2); lcd.print("offset= "); lcd.print(phCalOffset, 3);
        lcd.setCursor(0, 3); lcd.print("Kalibrasi selesai");
        beepSimpan();
        Serial.printf("[KAL] pH BARU: slope=%.6f offset=%.6f (EEPROM)\n", phCalSlope, phCalOffset);
        return;
    }

    if (kalMode == KAL_TDS) {
        float measured = readTDSStable(TDS_PIN, waterTemp, 10, 50);
        if (measured < 1.0f) {
            kalMode = KAL_OFF;
            lcd.clear(); lcd.setCursor(0, 0); lcd.print("TDS GAGAL");
            lcd.setCursor(0, 1); lcd.print("Probe kering/aneh?");
            beepAlarm();
            Serial.println("[KAL] TDS gagal: pembacaan terlalu rendah");
            return;
        }
        tdsCalFactor = tdsCalFactor * (TDS_STANDARD_PPM / measured);
        saveTdsCal();
        resetKalmanFilter();
        kalMode = KAL_OFF;
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("TDS TERSIMPAN!");
        lcd.setCursor(0, 1); lcd.print("Ukur : "); lcd.print(measured, 0); lcd.print(" ppm");
        lcd.setCursor(0, 2); lcd.print("Faktor: "); lcd.print(tdsCalFactor, 3);
        lcd.setCursor(0, 3); lcd.print("Kalibrasi selesai");
        beepSimpan();
        Serial.printf("[KAL] TDS: %.1f ppm -> %.0f ppm | faktor %.3f\n", measured, TDS_STANDARD_PPM, tdsCalFactor);
    }
}

// Cek push button kalibrasi (non-blocking) — dipanggil tiap loop.
void cekTombolKalibrasi() {
    bool lepas = (digitalRead(BUTTON_PIN) == HIGH);   // INPUT_PULLUP: HIGH = dilepas
    unsigned long now = millis();

    if (kalMode != KAL_OFF && now - kalAktifSejak > KAL_TIMEOUT_MS) {
        batalModeKalibrasi();   // ditinggal terlalu lama -> batal otomatis
        return;
    }

    if (!lepas && !btnTekan) { btnTekan = true; btnSejak = now; btnTanda = 0; }

    if (!lepas && btnTekan) {
        unsigned long lama = now - btnSejak;
        if (kalMode == KAL_OFF) {
            if (btnTanda < 1 && lama >= BTN_SHORT_MS)   { btnTanda = 1; beepClick(); }
            if (btnTanda < 2 && lama >= BTN_CAL_TDS_MS) { btnTanda = 2; beepClick(); beepClick(); }
        } else {
            if (btnTanda < 3 && lama >= BTN_BATAL_MS)   { btnTanda = 3; beepClick(); }
        }
        return;
    }

    if (lepas && btnTekan) {
        btnTekan = false;
        unsigned long lama = now - btnSejak;
        if (kalMode == KAL_OFF) {
            if (lama < BTN_SHORT_MS) {
                lcd.clear();
                lcd.setCursor(0, 0); lcd.print("TES BUZZER");
                lcd.setCursor(0, 1); lcd.print("Harus terdengar 2x");
                lcd.setCursor(0, 2); lcd.print("Jika tidak -> cek");
                lcd.setCursor(0, 3); lcd.print("kabel buzzer (IO26)");
                beepClick(); delay(150); beepSuccess();
                Serial.println("[BTN] Tes buzzer");
            } else if (lama < BTN_CAL_TDS_MS) {
                masukModeKalibrasi(KAL_PH);
            } else {
                masukModeKalibrasi(KAL_TDS);
            }
        } else {
            if (lama < BTN_BATAL_MS) konfirmasiLangkahKalibrasi();
            else                     batalModeKalibrasi();
        }
    }
}

float readSingleTDS(int tdsPin, float wTemp) {
    int raw = 0;
    for (int j = 0; j < 5; j++) { raw += analogRead(tdsPin); delayMicroseconds(100); }
    raw /= 5;
    float v  = raw * (3.3f / 4095.0f);
    float cc = 1.0f + 0.02f * (wTemp - 25.0f);
    float vc = v / cc;
    return (133.42f * vc * vc * vc - 255.86f * vc * vc + 857.39f * vc) * 0.5f * tdsCalFactor;   // x0.5 = konversi EC->TDS standar DFRobot
}

float readTDSStable(int tdsPin, float wTemp, int samples, int delayMs) {
    float sum = 0;
    for (int i = 0; i < samples; i++) {
        sum += readSingleTDS(tdsPin, wTemp);
        unsigned long t0 = millis();
        while (millis() - t0 < (unsigned long)delayMs) { delay(5); }
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

    // DHT22 mensyaratkan jeda MINIMAL 2 DETIK antar pembacaan (sampling
    // 0.5 Hz per datasheet). Mode warmup dan mode kalibrasi memanggil fungsi
    // ini tiap 1 DETIK, sehingga separuh bacaan keluar sebagai NaN -> LCD
    // menampilkan "Err" dan nilai tidak pernah terkirim ke website.
    // Nilai terakhir yang VALID juga dipertahankan: satu bacaan gagal tidak
    // boleh menimpa angka yang sebelumnya benar.
    if (now - lastDhtRead >= 2000UL) {
        lastDhtRead = now;
        float t = dht.readTemperature();
        float h = dht.readHumidity();
        if (!isnan(t)) airTemp  = t + DHT_TEMP_OFFSET;
        if (!isnan(h)) humidity = h + DHT_HUM_OFFSET;
    }

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

void turnOffSSR() {
    digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL);
    ssrActive = false; fillingActive = false; bothLowStartMs = 0; fillingStartMs = 0;
}

void setupSSRPin() {
    pinMode(SSR_SOLENOID, OUTPUT);
    digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL); delay(50);
}

// ===================================================
// BUZZER: pola suara untuk feedback & alarm
//   beepClick    = perintah diterima (web/kran)
//   beepSuccess  = kondisi baik tercapai (air penuh / sistem siap)
//   beepAlarm    = sensor abnormal / switch error (3x)
//   beepEmergency= darurat: emergency stop / system lockout (5x panjang)
// ===================================================
void beep(int onMs, int offMs) {
    digitalWrite(BUZZER_PIN, HIGH); delay(onMs);
    digitalWrite(BUZZER_PIN, LOW);  delay(offMs);
}

void setupBuzzer() {
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepClick()   { beep(40, 0); }
void beepSuccess() { beep(80, 60); beep(120, 0); }
void beepAlarm()   { for (int i = 0; i < 3; i++) beep(100, 70); }
void beepEmergency() { for (int i = 0; i < 5; i++) beep(250, 120); }
void beepSimpan()    { beep(150, 80); beep(150, 0); }   // 2 bip panjang = tersimpan
void beepBatal()     { beep(400, 0); }                  // 1 bip panjang  = dibatalkan

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
    } else if (pg == 1) {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("TDS: "); lcd.print(tdsValue, 0); lcd.print(" ppm");
        lcd.setCursor(0, 1);
        lcd.print("Level: ");
        if      (switchLogicError) lcd.print("ERROR!!");
        else if (g_highSwitch)     lcd.print("PENUH ");
        else if (g_lowSwitch)      lcd.print("SEDANG");
        else                       lcd.print("KURANG");
        lcd.setCursor(0, 2);
        if      (systemLockout)    lcd.print("RELAY: LOCKED!!!");
        else if (switchLogicError) lcd.print("RELAY: ERR-FROZEN");
        else if (emergencyStop)    lcd.print("RELAY: EMERGENCY");
        else if (offlineModeActive)lcd.print("RELAY: OFFLINE-OF");
        else if (manualOverride)   { lcd.print("RELAY: "); lcd.print(ssrActive ? "MANUAL-ON" : "MANUAL-OF"); }
        else                       { lcd.print("RELAY: "); lcd.print(ssrActive ? "ON (isi) " : "OFF      "); }
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
    } else {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("Tombol kalibrasi:");
        lcd.setCursor(0, 1); lcd.print("Pendek = tes buzzer");
        lcd.setCursor(0, 2); lcd.print("1 bip  = lepas: pH");
        lcd.setCursor(0, 3); lcd.print("2 bip  = lepas: TDS");
    }
    pg = (pg + 1) % 3;
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
// PENGIRIMAN DATA SENSOR MANDIRI KE DATABASE WEBSITE (REST API)
// Mengirim seluruh data sensor langsung ke Laravel tanpa bergantung browser terbuka
// ==============================================================================
unsigned long lastRestSaveTime = 0;
#define REST_SAVE_INTERVAL 30000UL // Kirim update ke web setiap 30 detik

void sendSensorDataToWebsiteREST() {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    WiFiClientSecure clientSecure;
    clientSecure.setInsecure();

    String saveUrl = "https://harvesthouse.biz.id/api/save-sensor";
    http.begin(clientSecure, saveUrl);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", website_api_key);
    http.setTimeout(5000);

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
    if (phValue >= 0.0f && phValue <= 14.0f) {
        doc["ph"]      = roundf(phValue  * 10.0f) / 10.0f;   // kirim hanya jika wajar (0-14)
    }
    doc["tds"]         = (tdsValue < 50.0f) ? 0.0f : roundf(tdsValue * 10.0f) / 10.0f;
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
        String resp = http.getString();
        StaticJsonDocument<256> respDoc;
        if (!deserializeJson(respDoc, resp)) {
            // Sinkronisasi jam: RTC dikoreksi otomatis dari waktu server
            const char* serverTime = respDoc["server_time"] | "";
            if (strlen(serverTime) > 10) syncRTCFromServerTime(String(serverTime));

            const char* cmd = respDoc["command"] | "AUTO";
            if (strcmp(cmd, "FORCE_FILL") == 0) {
                Serial.println("[WEB COMMAND] Perintah Buka Kran Diterima!");
                manualOverride = true;
                safeSetSSR(true);
                beepClick();
            } else if (strcmp(cmd, "FORCE_STOP") == 0) {
                Serial.println("[WEB COMMAND] Perintah Tutup Kran Diterima!");
                manualOverride = true;
                safeSetSSR(false);
                beepClick();
            }
        }
    }
    http.end();
}

void setup() {
    Serial.begin(115200); delay(2000);
    Serial.println("\n=== HYDROPONICS v4.1 - REST API (TANPA MQTT) ===");

    delay(500);   // stabilisasi power modul I2C (sama seperti test_lcd)
    Wire.begin(SDA_PIN, SCL_PIN); Wire.setClock(100000);

    // Init LCD PERSIS urutan test_lcd (terbukti jalan): LCD dulu, Wire1/RTC belakangan.
    // Retry 3x - kalau tetap gagal, sistem tetap jalan tanpa LCD (pesan ke Serial).
    int lcdStatus = -1;
    for (int i = 0; i < 3 && lcdStatus != 0; i++) {
        lcdStatus = lcd.begin(20, 4);
        if (lcdStatus) delay(200);
    }
    if (lcdStatus) Serial.printf("[LCD] GAGAL init (kode %d) - cek koneksi/kontras\n", lcdStatus);
    else           Serial.println("[LCD] OK 20x4");

    lcd.backlight();
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("LCD "); lcd.print(lcdStatus ? "GAGAL!     " : "OK 20x4    ");
    lcd.setCursor(0, 1); lcd.print("SYSTEM STARTING...");
    delay(1500);

    // Scan I2C bus LCD (diagnosa: backpack biasanya 0x27 / 0x3F)
    Serial.println("[I2C] Scan bus LCD (SDA=GPIO21 SCL=GPIO22):");
    bool i2cAda = false;
    for (uint8_t a = 0x20; a <= 0x3F; a++) {
        Wire.beginTransmission(a);
        if (Wire.endTransmission() == 0) {
            Serial.printf("  -> modul I2C ditemukan di alamat 0x%02X\n", a);
            i2cAda = true;
        }
    }
    if (!i2cAda) Serial.println("  -> TIDAK ADA perangkat! Cek kabel SDA/SCL/VCC/GND LCD");

    Wire1.begin(SDA2_PIN, SCL2_PIN); Wire1.setClock(100000);

    setupSSRPin(); setupBuzzer(); dht.begin(); ds18b20.begin();
    pinMode(BUTTON_PIN, INPUT_PULLUP);   // push button kalibrasi (GPIO32 ke GND)

    // Sensor pH DFRobot: siapkan EEPROM lalu muat parameter kalibrasi
    EEPROM.begin(64);
    ph.begin();
    loadTdsCal();   // muat faktor kalibrasi TDS dari EEPROM (alamat 32)
    loadPhCal();    // muat kalibrasi lab pH slope/offset (alamat 36 & 40)
    Serial.printf("[pH] kalibrasi lab: slope=%.6f offset=%.6f\n", phCalSlope, phCalOffset);
    Serial.printf("[TDS] Faktor kalibrasi: %.3f\n", tdsCalFactor);
    Serial.println("[CAL] Perintah: ENTERPH/CALPH/EXITPH (pH) | TDSCAL <ppm>/TDSRESET/TDSSTATUS (TDS)");

    if (!rtc.begin(&Wire1)) { Serial.println("RTC not found!"); }
    else if (rtc.lostPower()) { rtc.adjust(DateTime(F(__DATE__), F(__TIME__))); }

    pinMode(WATER_SWITCH_LOW_PIN,  INPUT_PULLUP);
    pinMode(WATER_SWITCH_HIGH_PIN, INPUT_PULLUP);
    beepSuccess();   // tanda sistem siap

    // Reset semua state
    switchLogicError = false; errorStartTime = 0; impossibleStartMs = 0;
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

    lcd.clear(); lcd.setCursor(0, 0); lcd.print("Connecting WiFi...");
    WiFi.begin(ssid, password);

    int wt = 0;
    while (WiFi.status() != WL_CONNECTED && wt < 20) { delay(500); wt++; }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("WiFi OK");
        lcd.setCursor(0, 1); lcd.print(WiFi.localIP()); delay(1500);
        syncRTCWithNTP();

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
    previousSensorRead = t0; previousLCDUpdate = t0;
    previousTDSRead = t0;
    lastSafetyCheck = t0; lastKalmanResetTime = t0;

    Serial.println("Setup selesai v4.0");
}

void loop() {
    unsigned long now = millis();

    updateGlobalSwitchState();
    cekTombolKalibrasi();   // push button: kalibrasi pH/TDS + tes buzzer

    if (kalMode != KAL_OFF) {   // mode kalibrasi: probe di luar air -> kontrol isi air OFF
        if (ssrActive) safeSetSSR(false);
        if (now - previousSensorRead >= 1000UL) { previousSensorRead = now; readAllSensors(); }
        if (now - previousKalLCD   >= 500UL)  { previousKalLCD   = now; tampilLayarKalibrasi(); }
        return;
    }

    if (now - lastSafetyCheck >= SAFETY_CHECK_INTERVAL) {
        lastSafetyCheck = now;
        checkSafetySystems();
    }

    if (emergencyStop || systemLockout) {
        tryResetEmergency();
    }

    if (!warmup_complete) {
        checkWarmupStatus();
        if (ssrActive) safeSetSSR(false);
        reconnectWiFi();
        if (now - previousSensorRead >= 1000UL) { previousSensorRead = now; readAllSensors(); }
        if (now - previousLCDUpdate  >= 1000UL) { previousLCDUpdate  = now; displayWarmupScreen(); }
        return;
    }

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

    if (now - previousLCDUpdate >= LCD_UPDATE_INTERVAL) { previousLCDUpdate = now; updateLCD(); }

    // Kirim sinkronisasi sensor langsung ke Laravel setiap 30 detik
    if (now - lastRestSaveTime >= REST_SAVE_INTERVAL) {
        lastRestSaveTime = now;
        sendSensorDataToWebsiteREST();
    }

    // Kalibrasi pH DFRobot via Serial Monitor
    handlePHCalibration();
}
// ===================================================
// SINKRONISASI RTC DARI JAM SERVER (anti-drift)
// Format: ISO UTC, mis. 2026-10-01T15:18:04+00:00
// Dipanggil tiap kali data terkirim; RTC dikoreksi jika meleset > 30 detik
// ===================================================
void syncRTCFromServerTime(const String& isoUtc) {
    int y, mo, d, h, mi, s;
    if (sscanf(isoUtc.c_str(), "%d-%d-%dT%d:%d:%d", &y, &mo, &d, &h, &mi, &s) != 6) return;

    DateTime serverWita = DateTime(y, mo, d, h, mi, s) + TimeSpan(8 * 3600); // UTC -> WITA
    long selisih = (long)serverWita.unixtime() - (long)rtc.now().unixtime();
    if (selisih < 0) selisih = -selisih;

    if (selisih > 30) {
        rtc.adjust(serverWita);
        Serial.printf("[TIME] RTC dikoreksi dari server (meleset %ld detik)\n", selisih);
    }
}
