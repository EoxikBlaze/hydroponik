#!/usr/bin/env python3
"""
Generator part Fritzing (.fzp + SVG) — proyek hidroponik HarvestHouse.
KONVENSI (diverifikasi dari file .fz nyata Fritzing 1.0.8):
  - SVG px-style: 1 unit viewBox = 1 scene px = 1/90 in => pitch header 100mil = 9 unit
  - Connector terminal: rect 1x1, center tepat di kelipatan 9 (grid 0.1in)
  - fzp <layers image="<view>/<file>.svg"> (subfolder) agar cocok dgn zip entry
    .fzpz/.fzz: svg.<view>.<file>.svg
Keluaran: parts_build/<Name>.fzp + <Name>_{breadboard,schematic,pcb,icon}.svg
"""
import os, html

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "parts_build")
os.makedirs(OUT, exist_ok=True)

def rect(x, y, w, h, fill, extra=""):
    return f'<rect x="{x:g}" y="{y:g}" width="{w:g}" height="{h:g}" fill="{fill}" {extra}/>'

def text(x, y, s, size=3.2, fill="#111", anchor="start", rotate=None, weight="normal"):
    tr = f' transform="rotate({rotate[0]} {rotate[1]:g} {rotate[2]:g})"' if rotate else ""
    return (f'<text x="{x:g}" y="{y:g}" font-size="{size:g}" fill="{fill}" '
            f'text-anchor="{anchor}" font-family="Helvetica,Arial,sans-serif" '
            f'font-weight="{weight}"{tr}>{html.escape(s)}</text>')

def conn(x, y, i):
    return (f'<rect id="connector{i}pin" x="{x-0.5:g}" y="{y-0.5:g}" width="1" '
            f'height="1" fill="none"/>')

def conn_pad(x, y, i):
    return (f'<rect id="connector{i}pad" x="{x-1:g}" y="{y-1:g}" width="2" '
            f'height="2" fill="none"/>')

def svg_doc(w, h, layer_id, body):
    return ('<?xml version="1.0" encoding="UTF-8"?>\n'
            f'<svg xmlns="http://www.w3.org/2000/svg" width="{w:g}" height="{h:g}" '
            f'viewBox="0 0 {w:g} {h:g}" version="1.1">\n'
            f'<g id="{layer_id}">\n{body}\n</g>\n</svg>\n')

def build_fzp(name, title, label, desc, family, pins, props=None, module_id=None):
    props = props or {}
    prop_xml = "".join(
        f'<property name="{html.escape(k)}">{html.escape(v)}</property>\n'
        for k, v in props.items())
    prop_xml += f'<property name="family">{html.escape(family)}</property>\n'
    conn_xml = []
    for cid, pname, pdesc in pins:
        conn_xml.append(f'''<connector id="{cid}" type="male" name="{html.escape(pname)}">
<description>{html.escape(pdesc)}</description>
<views>
<breadboardView><p layer="breadboard" svgId="{cid}pin"/></breadboardView>
<schematicView><p layer="schematic" svgId="{cid}pin"/></schematicView>
<pcbView><p layer="copper0" svgId="{cid}pad"/><p layer="copper1" svgId="{cid}pad"/></pcbView>
</views>
</connector>''')
    return f'''<?xml version="1.0" encoding="UTF-8"?>
<module fritzingVersion="1.0.8" moduleId="{module_id or name}" referenceFile="{name}.fzp">
<version>1.0</version>
<author>HarvestHouse</author>
<title>{html.escape(title)}</title>
<label>{html.escape(label)}</label>
<date>2026-10-10</date>
<url>https://harvesthouse.biz.id</url>
<tags>
<tag>harvesthouse</tag>
<tag>hidroponik</tag>
</tags>
<properties>
{prop_xml}</properties>
<description>{html.escape(desc)}</description>
<views>
<iconView><layers image="icon/{name}_icon.svg"><layer layerId="icon"/></layers></iconView>
<breadboardView><layers image="breadboard/{name}_breadboard.svg"><layer layerId="breadboard"/></layers></breadboardView>
<schematicView><layers image="schematic/{name}_schematic.svg"><layer layerId="schematic"/></layers></schematicView>
<pcbView>
<layers image="pcb/{name}_pcb.svg">
<layer layerId="copper0"/><layer layerId="copper1"/>
<layer layerId="silkscreen"/><layer layerId="outline"/>
</layers>
</pcbView>
</views>
<connectors>
{chr(10).join(conn_xml)}
</connectors>
</module>
'''

def write_part(name, title, label, desc, family, pins, bb, sch, pcb, icon, w, h,
               props):
    for view, content in (("breadboard", bb), ("schematic", sch), ("pcb", pcb),
                          ("icon", icon)):
        with open(os.path.join(OUT, f"{name}_{view}.svg"), "w") as f:
            f.write(content)
    with open(os.path.join(OUT, f"{name}.fzp"), "w") as f:
        f.write(build_fzp(name, title, label, desc, family, pins, props))

# ------------------------------------------------------- modul pin-di-bawah
def module_part(name, title, label, desc, family, pin_names, pin_descs=None,
                color="#1565c0", body_h=30, props=None, font_label=None,
                edge="bottom"):
    """Pin di tepi bawah: pin i di (9(i+1), body_h+6) — semua kelipatan 9."""
    n = len(pin_names)
    pin_descs = pin_descs or [""] * n
    w = 9 * (n - 1) + 18
    h = body_h + 9
    if edge == "bottom":
        py = body_h + 6
        by = 3
    else:                              # pin di tepi ATAS: pin y=0, badan mulai y=6
        py = 0
        by = 6
    assert py % 9 == 0, f"{name}: y pin harus kelipatan 9"
    body = [rect(3, by, w - 6, body_h, color, 'rx="2"'),
            f'<rect x="3" y="{by}" width="{w-6:g}" height="{body_h:g}" fill="none" '
            f'stroke="#0b3d5c" stroke-width="0.6" rx="2"/>',
            text(w / 2, by + 8, font_label or label, size=4.4, fill="#ffffff",
                 anchor="middle", weight="bold")]
    pins = []
    for i, pn in enumerate(pin_names):
        x = 9 * (i + 1)
        body.append(conn(x, py, i))
        leg_end = py + 5 if edge == "top" else py - 5
        lbl_y = py + 6.5 if edge == "top" else py - 6.5
        rot = (90, x, lbl_y) if edge == "top" else (-90, x, lbl_y)
        body.append(f'<line x1="{x:g}" y1="{py:g}" x2="{x:g}" y2="{leg_end:g}" '
                    f'stroke="#9aa0a6" stroke-width="1.4"/>')
        body.append(text(x, lbl_y, pn, size=3.4, fill="#e8f5e9", anchor="start",
                         rotate=rot))
        pins.append((f"connector{i}", pn, pin_descs[i]))

    # schematic
    sh = max(20, n * 9 + 6)
    sc = [rect(14, 6, 40, sh - 10, "#fdf6e3", 'stroke="#555" stroke-width="0.5"'),
          text(34, 14, label, size=4, anchor="middle", weight="bold")]
    for i, pn in enumerate(pin_names):
        y = 16 + i * 9
        sc.append(f'<line x1="14" y1="{y:g}" x2="4" y2="{y:g}" stroke="#333" stroke-width="0.5"/>')
        sc.append(f'<rect id="connector{i}pin" x="3.5" y="{y-0.5:g}" width="1" height="1" fill="none"/>')
        sc.append(text(17, y + 1.4, pn, size=3.2))
    sch = "\n".join(sc)

    pc = [f'<rect x="2" y="2" width="{w-4:g}" height="{h-4:g}" fill="#0d5c2f" rx="2"/>']
    for i in range(n):
        x = 9 * (i + 1)
        pc.append(f'<circle cx="{x:g}" cy="{py:g}" r="2.2" fill="#c0a030"/>')
        pc.append(conn_pad(x, py, i))
    pcb = "\n".join(pc)

    icon = svg_doc(w, h, "icon", "\n".join([rect(3, 3, w - 6, h - 6, color, 'rx="3"'),
        text(w / 2, h / 2 + 1.5, label, size=5, fill="#fff", anchor="middle", weight="bold")]))
    write_part(name, title, label, desc, family, pins,
               svg_doc(w, h, "breadboard", "\n".join(body)),
               svg_doc(w, h, "schematic", sch),
               svg_doc(w, h, "copper0", pcb), icon, w, h, props)
    return dict(name=name, pins=pin_names, conn=[f"connector{i}" for i in range(n)],
                w=w, h=h, body_h=body_h,
                pts=[(9 * (i + 1), py) for i in range(n)])

# ------------------------------------------------------------ ESP32 DevKit
# Urutan pin fisik DOIT DevKit V1 30-pin, USB di atas (3 sumber sepakat:
# KiCad footprint tyme2tb/doit-esp32-devkit-v1, pinout CIRCUITSTATE, espboards.dev)
ESP32_LEFT = ["VIN(5V)", "GND", "IO13", "IO12", "IO14", "IO27", "IO26", "IO25",
              "IO33", "IO32", "IO35", "IO34", "IO39", "IO36", "EN"]
ESP32_RIGHT = ["3V3", "GND", "IO15", "IO2", "IO4", "IO16", "IO17", "IO5",
               "IO18", "IO19", "IO21", "RX0", "TX0", "IO22", "IO23"]

def esp32_part():
    name = "ESP32-DevKitV1-30"
    xl, xr, y0 = 9, 99, 18         # jarak baris 90 unit = 1000 mil
    w, h = 108, 156
    body = [rect(3, 3, w - 6, h - 6, "#0b3d5c", 'rx="3"'),
            f'<rect x="3" y="3" width="{w-6:g}" height="{h-6:g}" fill="none" '
            f'stroke="#082b40" stroke-width="0.8" rx="3"/>',
            rect(45, 0, 18, 7, "#b0b8bd", 'rx="1.5"'),
            text(54, 5, "USB", size=2.8, fill="#333", anchor="middle"),
            rect(24, 9, 10, 5, "#d0d4d8", 'rx="1"'),
            text(29, 12.8, "EN", size=2.4, fill="#222", anchor="middle"),
            rect(74, 9, 12, 5, "#d0d4d8", 'rx="1"'),
            text(80, 12.8, "BOOT", size=2.2, fill="#222", anchor="middle"),
            rect(30, 54, 48, 54, "#9aa4ac", 'rx="2"'),
            text(54, 76, "ESP-WROOM-32", size=3.2, fill="#263238", anchor="middle"),
            text(54, 84, "WiFi+BT", size=3, fill="#37474f", anchor="middle"),
            text(54, h - 8, "ESP32 DevKit V1 (30p)", size=3.8, fill="#e3f2fd",
                 anchor="middle", weight="bold")]
    for i, nm in enumerate(ESP32_LEFT):
        y = y0 + 9 * i
        body += [conn(xl, y, i),
                 text(xl + 4, y + 1.3, nm, size=3.3, fill="#e3f2fd"),
                 f'<circle cx="{xl:g}" cy="{y:g}" r="1.6" fill="#ffd54f"/>']
    for i, nm in enumerate(ESP32_RIGHT):
        y = y0 + 9 * i
        body += [conn(xr, y, 15 + i),
                 text(xr - 4, y + 1.3, nm, size=3.3, fill="#e3f2fd", anchor="end"),
                 f'<circle cx="{xr:g}" cy="{y:g}" r="1.6" fill="#ffd54f"/>']

    pins = [(f"connector{i}", ESP32_LEFT[i], f"Header kiri pin {i+1}") for i in range(15)]
    pins += [(f"connector{15+i}", ESP32_RIGHT[i], f"Header kanan pin {i+1}") for i in range(15)]

    sc = [rect(30, 6, 60, 160, "#e3f2fd", 'stroke="#1565c0" stroke-width="0.6"'),
          text(60, 15, "ESP32 DevKit", size=4.5, anchor="middle", weight="bold")]
    for i in range(15):
        y = 22 + i * 9
        sc += [f'<line x1="30" y1="{y:g}" x2="20" y2="{y:g}" stroke="#333" stroke-width="0.5"/>',
               f'<rect id="connector{i}pin" x="19.5" y="{y-0.5:g}" width="1" height="1" fill="none"/>',
               text(33, y + 1.3, ESP32_LEFT[i], size=3.3),
               f'<line x1="90" y1="{y:g}" x2="100" y2="{y:g}" stroke="#333" stroke-width="0.5"/>',
               f'<rect id="connector{15+i}pin" x="99.5" y="{y-0.5:g}" width="1" height="1" fill="none"/>',
               text(87, y + 1.3, ESP32_RIGHT[i], size=3.3, anchor="end")]
    sch = "\n".join(sc)

    pc = [f'<rect x="2" y="2" width="{w-4:g}" height="{h-4:g}" fill="#0d5c2f" rx="3"/>']
    for i in range(15):
        y = y0 + 9 * i
        pc += [f'<circle cx="{xl:g}" cy="{y:g}" r="2.2" fill="#c0a030"/>',
               conn_pad(xl, y, i),
               f'<circle cx="{xr:g}" cy="{y:g}" r="2.2" fill="#c0a030"/>',
               conn_pad(xr, y, 15 + i)]
    pcb = "\n".join(pc)

    icon = svg_doc(w, h, "icon", "\n".join([rect(3, 3, w - 6, h - 6, "#0b3d5c", 'rx="3"'),
        text(w / 2, h / 2, "ESP32", size=9, fill="#fff", anchor="middle", weight="bold")]))
    write_part(name, "ESP32 DevKit V1 30-pin (DOIT)", "ESP32",
               "Papan ESP32 DOIT DevKit V1 30-pin. Layout: kolom kiri VIN..EN, "
               "kolom kanan 3V3..IO23, USB di atas. Jarak baris 1000 mil.",
               "mcu", pins, svg_doc(w, h, "breadboard", "\n".join(body)),
               svg_doc(w, h, "schematic", sch), svg_doc(w, h, "copper0", pcb),
               icon, w, h, {"package": "DOIT-30"})
    return dict(name=name, w=w, h=h, xl=xl, xr=xr, y0=y0, body_h=h)

# --------------------------------------------------------------- relay 1-ch
def relay_part():
    name = "Relay-5V-1ch"
    in_pins = ["IN", "VCC", "GND"]
    out_pins = ["NO", "COM", "NC"]
    w, h, body_h = 72, 63, 48
    py = body_h + 6                      # 54 (kelipatan 9)
    body = [rect(3, 3, w - 6, body_h, "#b71c1c", 'rx="3"'),
            f'<rect x="3" y="3" width="{w-6:g}" height="{body_h:g}" fill="none" '
            f'stroke="#7f0000" stroke-width="0.7" rx="3"/>',
            text(33, 11, "RELAY 5V", size=4.4, fill="#fff", anchor="middle", weight="bold"),
            text(33, 17, "SRD-05VDC (AL)", size=2.8, fill="#ffcdd2", anchor="middle"),
            rect(12, 24, 27, 18, "#d32f2f", 'rx="1"'),
            text(25.5, 35, "RELAY", size=3, fill="#fff", anchor="middle"),
            rect(42, 22, 18, 22, "#9e9e9e", 'rx="1"')]
    pins = []
    for i, pn in enumerate(in_pins):
        x = 9 * (i + 1)
        body += [conn(x, py, i),
                 f'<line x1="{x:g}" y1="{py:g}" x2="{x:g}" y2="{py-5:g}" stroke="#9aa0a6" stroke-width="1.4"/>',
                 text(x, py - 6.5, pn, size=3.4, fill="#ffe0e0", anchor="start",
                      rotate=(-90, x, py - 6.5))]
        pins.append((f"connector{i}", pn,
                     {"IN": "Input active-LOW dari GPIO25", "VCC": "Supply 5V",
                      "GND": "Ground"}[pn]))
    for j, pn in enumerate(out_pins):
        y = 27 + 9 * j                    # 27,36,45 (kelipatan 9)
        cid = 3 + j
        body += [conn(63, y, cid),
                 f'<circle cx="51" cy="{y:g}" r="2.6" fill="#616161" stroke="#424242" stroke-width="0.5"/>',
                 f'<line x1="53.6" y1="{y:g}" x2="63" y2="{y:g}" stroke="#9aa0a6" stroke-width="1.4"/>',
                 text(61.5, y - 1.8, pn, size=3, fill="#fff", anchor="end")]
        pins.append((f"connector{cid}", pn,
                     {"NO": "Normally open -> + solenoid", "COM": "Common -> +12V PSU",
                      "NC": "Normally close (tidak dipakai)"}[pn]))

    sc = [rect(16, 6, 44, 51, "#fdecea", 'stroke="#b71c1c" stroke-width="0.6"'),
          text(38, 14, "Relay 5V", size=4, anchor="middle", weight="bold")]
    for i in range(3):
        y = 24 + i * 9
        sc += [f'<line x1="16" y1="{y:g}" x2="6" y2="{y:g}" stroke="#333" stroke-width="0.5"/>',
               f'<rect id="connector{i}pin" x="5.5" y="{y-0.5:g}" width="1" height="1" fill="none"/>',
               text(19, y + 1.4, in_pins[i], size=3.2)]
    for j in range(3):
        y = 24 + j * 9
        sc += [f'<line x1="60" y1="{y:g}" x2="70" y2="{y:g}" stroke="#333" stroke-width="0.5"/>',
               f'<rect id="connector{3+j}pin" x="69.5" y="{y-0.5:g}" width="1" height="1" fill="none"/>',
               text(57, y + 1.4, out_pins[j], size=3.2, anchor="end")]
    sch = "\n".join(sc)

    pc = [f'<rect x="2" y="2" width="{w-4:g}" height="{h-4:g}" fill="#0d5c2f" rx="3"/>']
    for i in range(3):
        x = 9 * (i + 1)
        pc += [f'<circle cx="{x:g}" cy="{py:g}" r="2.2" fill="#c0a030"/>', conn_pad(x, py, i)]
    for j in range(3):
        y = 27 + 9 * j
        pc += [f'<circle cx="63" cy="{y:g}" r="2.6" fill="#c0a030"/>', conn_pad(63, y, 3 + j)]
    pcb = "\n".join(pc)
    icon = svg_doc(w, h, "icon", "\n".join([rect(3, 3, w - 6, h - 6, "#b71c1c", 'rx="3"'),
        text(w / 2, h / 2, "RELAY", size=5.5, fill="#fff", anchor="middle", weight="bold")]))
    write_part(name, "Modul Relay 5V 1-channel", "RELAY",
               "Relay 5V (firmware: ACTIVE-LOW, RELAY_ON_LEVEL=LOW). IN=GPIO25, "
               "koil dari 5V. Sisi beban COM/NO untuk solenoid 12V.", "relay", pins,
               svg_doc(w, h, "breadboard", "\n".join(body)),
               svg_doc(w, h, "schematic", sch), svg_doc(w, h, "copper0", pcb),
               icon, w, h, {"package": "SRD-05VDC"})
    return dict(name=name, pins=in_pins + out_pins,
                conn=[f"connector{i}" for i in range(6)],
                w=w, h=h, body_h=body_h,
                in_pts=[(9 * (i + 1), py) for i in range(3)],
                out_pts=[(63, 27 + 9 * j) for j in range(3)])

if __name__ == "__main__":
    p = {}
    p["ESP32"] = esp32_part()
    p["DHT22"] = module_part(
        "DHT22-Module", "DHT22 Modul 3-pin", "DHT22",
        "Modul DHT22 3-pin (suhu & lembab udara). DATA ke GPIO4; modul umumnya "
        "sudah ada pull-up onboard (10k eksternal hanya untuk sensor polos 4-pin). "
        "CEK SILKSCREEN modul: urutan bisa VCC-DATA-GND atau -/OUT/+.",
        "sensor", ["VCC", "DATA", "GND"],
        ["Supply 3.3V", "Data (GPIO4)", "Ground"], color="#1565c0", body_h=30)
    p["DS18B20"] = module_part(
        "DS18B20-Module", "DS18B20 Modul (probe air)", "DS18B20",
        "Modul DS18B20 probe tahan air. DATA ke GPIO18 + pull-up 4.7k ke 3V3. "
        "CEK SILKSCREEN modul: urutan bisa VCC-DATA-GND atau GND-DATA-VCC.",
        "sensor", ["VCC", "DATA", "GND"],
        ["Supply 3.3V", "1-Wire data (GPIO18)", "Ground"], color="#2e7d32", body_h=30)
    p["LCD"] = module_part(
        "LCD20x4-I2C", "LCD 20x4 I2C (PCF8574)", "LCD20x4",
        "LCD 20x4 backpack I2C, alamat 0x27 (clone bisa 0x3F -> I2C scan). "
        "Header: GND VCC SDA SCL. VCC disarankan 3V3 agar pull-up I2C di 3.3V.",
        "display", ["GND", "VCC", "SDA", "SCL"],
        ["Ground", "Supply (3V3, aman untuk ESP32)", "I2C SDA (GPIO21)",
         "I2C SCL (GPIO22)"], color="#37474f", body_h=48)
    p["RTC"] = module_part(
        "RTC-DS3231", "RTC DS3231", "DS3231",
        "Modul RTC DS3231 (Wire1: SDA=GPIO16, SCL=GPIO17). Urutan header umum: "
        "32K SQW SCL SDA VCC GND. VCC 3V3 agar pull-up I2C tidak 5V. Baterai CR2032.",
        "clock", ["32K", "SQW", "SCL", "SDA", "VCC", "GND"],
        ["Tidak dipakai", "Tidak dipakai", "I2C SCL (GPIO17)", "I2C SDA (GPIO16)",
         "Supply 3.3V", "Ground"], color="#4e342e", body_h=39)
    p["pH"] = module_part(
        "pH-SEN0161-V2", "pH DFRobot SEN0161-V2", "pH",
        "Papan pH Gravity DFRobot SEN0161-V2 (analog). Label papan: - + A. "
        "Supply 3.3-5.5V, output 0-3.0V -> aman langsung ke ADC GPIO33 (tanpa divider).",
        "sensor", ["-", "+", "A"],
        ["Ground (kabel hitam)", "Supply 5V (kabel merah)",
         "Analog out 0-3V -> GPIO33 (kabel kuning)"], color="#6a1b9a", body_h=39)
    p["TDS"] = module_part(
        "TDS-Module", "Sensor TDS SEN0244 (analog)", "TDS",
        "Sensor TDS DFRobot SEN0244 (analog). Label papan: - + A. Supply 3.3-5.5V, "
        "output 0-2.3V -> aman ke ADC GPIO34 (input-only, tetap valid untuk analog).",
        "sensor", ["-", "+", "A"],
        ["Ground (kabel hitam)", "Supply 3.3V (kabel merah)",
         "Analog out 0-2.3V -> GPIO34 (kabel kuning)"], color="#00695c", body_h=39)
    p["RLY"] = relay_part()
    p["BZ"] = module_part(
        "Buzzer-Module", "Buzzer Aktif Modul", "BUZZER",
        "Buzzer aktif modul (alarm & feedback suara). IN dari GPIO26; daya dari "
        "3V3 (<=30mA, jaga agar <20mA per pin GPIO).",
        "audio", ["GND", "VCC", "IN"],
        ["Ground", "Supply 3.3V", "Input dari GPIO26"], color="#263238", body_h=30)
    p["FSB"] = module_part(
        "FloatSwitch", "Float Switch Magnetik Batang", "FS",
        "Float switch batang 2 kabel (NO). Kabel C ke GPIO (INPUT_PULLUP), kabel NO "
        "ke GND. Terendam = kontak tutup = pin LOW. GPIO14=bawah, GPIO27=atas.",
        "switch", ["C", "NO"],
        ["Ke GPIO (14 bawah / 27 atas)", "Ke GND"], color="#455a64", body_h=21)
    p["BTN"] = module_part(
        "PushButton", "Tombol Push (NO)", "BTN",
        "Tombol tekan NO antara GPIO32 dan GND (INPUT_PULLUP, aktif LOW).",
        "switch", ["A", "B"], ["Ke GPIO32", "Ke GND"], color="#37474f", body_h=21)
    p["R"] = module_part(
        "Resistor-Axial", "Resistor Axial", "R",
        "Resistor axial pull-up. R 10k: DHT22 DATA-VCC; R 4.7k: DS18B20 DATA-VCC.",
        "resistor", ["A", "B"], ["Kaki 1", "Kaki 2"], color="#8d6e63",
        body_h=21, props={"Resistance": "10k"})
    p["D1"] = module_part(
        "Diode-1N4007", "Dioda 1N4007", "D",
        "Dioda flyback paralel kumparan solenoid: KATHODE (garis strip) ke kaki "
        "(+) solenoid, ANODA ke kaki (-)/GND PSU. Pin di tepi atas.",
        "diode", ["K", "A"],
        ["Kathode garis (ke kaki + solenoid)", "Anoda (ke kaki - / GND PSU)"],
        color="#424242", body_h=21, edge="top")
    p["PSU"] = module_part(
        "PSU-12V", "Power Supply 12V", "PSU12V",
        "Adaptor/PSU 12V untuk sisi beban relay (solenoid). Terisolasi dari sisi "
        "ESP32 (lewat kontak relay).",
        "power", ["+12V", "GND"], ["+12V ke COM relay", "GND / - ke solenoid"],
        color="#880e4f", body_h=30)
    p["SOL"] = module_part(
        "SolenoidValve", "Solenoid Valve 12V", "SOLENOID",
        "Kran solenoid 12V (isi air), digerakkan kontak relay COM/NO. Dioda flyback "
        "1N4007 paralel di kaki (+).",
        "actuator", ["+", "-"], ["Ke NO relay & katode D1", "Ke GND PSU 12V"],
        color="#bf360c", body_h=30)
    for k, v in p.items():
        print(f"{k:8} {v['name']:18} pins={v.get('pins')}")
