#!/usr/bin/env python3
"""
Rakit sketch Fritzing breadboard HarvestHouse -> harvesthouse_breadboard.fzz
Format mengikuti .fz nyata Fritzing 1.0.8 (diverifikasi dari Button.fzz dsb.):
  root <module> + project_properties + views + instances;
  instance: property*, title, views/breadboardView/geometry(+connectors);
  wire: moduleIdRef=WireModuleID, geometry x,y,x1,y1,x2,y2,wireFlags=64,
        wireExtras, connectors connector0/1;
  connect layer: target part -> "breadboardbreadboard", target wire -> "breadboardWire".
Koordinat: 1 px scene = 1 unit viewBox (90px = 1in), grid 9px = 0.1in.
"""
import os, re, zipfile, itertools, html

HERE = os.path.dirname(os.path.abspath(__file__))
PARTS = os.path.join(HERE, "parts_build")
OUT_FZZ = os.path.join(HERE, "harvesthouse_breadboard.fzz")
OUT_FZ = os.path.join(HERE, "harvesthouse_breadboard.fz")
OUT_PREVIEW = os.path.join(HERE, "preview_breadboard.svg")

# ============================================================ POSISI PART
# (moduleId, title, x, y, props)
INSTANCES = {
    "E":   ("ESP32-DevKitV1-30", "ESP32",        360, 108, {}),
    "DHT": ("DHT22-Module",      "DHT22",        513, 108, {}),
    "DS":  ("DS18B20-Module",    "DS18B20",      513, 171, {}),
    "RTC": ("RTC-DS3231",        "RTC-DS3231",   486, 243, {}),
    "LCD": ("LCD20x4-I2C",       "LCD20X4",      630, 108, {}),
    "pH":  ("pH-SEN0161-V2",     "pH-SEN0161",   243, 108, {}),
    "TDS": ("TDS-Module",        "TDS-SEN0244",  243, 189, {}),
    "FSB": ("FloatSwitch",       "FS-BAWAH",     234, 270, {}),
    "FSA": ("FloatSwitch",       "FS-ATAS",      234, 324, {}),
    "BTN": ("PushButton",        "TOMBOL",       234, 378, {}),
    "BZ":  ("Buzzer-Module",     "BUZZER",       243, 432, {}),
    "RLY": ("Relay-5V-1ch",      "RELAY",        198, 486, {}),
    "PSU": ("PSU-12V",           "PSU-12V",       99, 486, {}),
    "SOL": ("SolenoidValve",     "SOLENOID",      99, 567, {}),
    "D1":  ("Diode-1N4007",      "D1-FLYBACK",    99, 639, {}),
    "R1":  ("Resistor-Axial",    "R1-10K",       576, 144, {"Resistance": "10k"}),
    "R2":  ("Resistor-Axial",    "R2-4K7",       567, 216, {"Resistance": "4.7k"}),
}
# body box: (x, y, w, h) di koordinat scene — untuk cek silang kabel vs bodi part
BODIES = {}
def _mod_body(k, w, bh):
    _, _, x, y, _ = INSTANCES[k]
    BODIES[k] = (x + 3, y + 3, w - 6, bh)
for k, w, bh in [("DHT", 36, 30), ("DS", 36, 30), ("RTC", 63, 39), ("LCD", 45, 48),
                 ("pH", 36, 39), ("TDS", 36, 39), ("FSB", 27, 21), ("FSA", 27, 21),
                 ("BTN", 27, 21), ("BZ", 36, 30), ("PSU", 27, 30), ("SOL", 27, 30),
                 ("D1", 27, 21), ("R1", 27, 21), ("R2", 27, 21)]:
    _mod_body(k, w, bh)
BODIES["D1"] = (99 + 3, 639 + 6, 27 - 6, 21)
BODIES["E"] = (363, 111, 102, 150)
BODIES["RLY"] = (201, 489, 66, 48)

def pt(key, pin):
    """Titik absolut connector: pin = indeks (0-based) atau ('L'/'R', i) utk ESP32."""
    _, _, x, y, _ = INSTANCES[key]
    if key == "E":
        col, i = pin
        return (369 if col == "L" else 459, y + 18 + 9 * i)
    if key == "RLY":
        if isinstance(pin, str):            # out: "NO","COM","NC"
            idx = {"NO": 0, "COM": 1, "NC": 2}[pin]
            return (x + 63, y + 27 + 9 * idx)
        return (x + 9 * (pin + 1), y + 54)  # in: 0=IN,1=VCC,2=GND
    if key == "D1":                 # dioda: pin di tepi ATAS
        return (x + 9 * (pin + 1), y + 0)
    bh = BODIES[key][3]
    return (x + 9 * (pin + 1), y + bh + 6)

# ESP32 kiri (0-based): 0 VIN,1 GND,2 13,3 12,4 14,5 27,6 26,7 25,8 33,9 32,10 35,11 34,12 39,13 36,14 EN
# ESP32 kanan: 0 3V3,1 GND,2 15,3 2,4 4,5 16,6 17,7 5,8 18,9 19,10 21,11 RX,12 TX,13 22,14 23

# ============================================================ ROUTES
# tiap route: (net, warna, [titik]) — titik awal/akhir pin ref ("KEY",idx) atau koordinat (x,y)
NETS = []
def route(net, color, *pts):
    NETS.append((net, color, list(pts)))

BLK, RED, ORG, GRN, YEL, BLU, PUR, GRY = ("#000000", "#e53935", "#fb8c00",
                                          "#43a047", "#fdd835", "#1e88e5",
                                          "#8e44ad", "#607d8b")
# ---- sinyal kanan
route("IO4-DHT22",  GRN, ("E", ("R", 4)), (531, 153), ("DHT", 1))
route("IO18-DS18B20", PUR, ("E", ("R", 8)), (531, 225), ("DS", 1))
route("IO21-LCD-SDA", YEL, ("E", ("R", 10)), (477, 216), (477, 342),
      (657, 342), (657, 162), ("LCD", 2))
route("IO22-LCD-SCL", YEL, ("E", ("R", 13)), (474, 243), (474, 360),
      (666, 360), (666, 162), ("LCD", 3))
route("IO16-RTC-SDA", YEL, ("E", ("R", 5)), (486, 171), (486, 324),
      (522, 324), (522, 288), ("RTC", 3))
route("IO17-RTC-SCL", YEL, ("E", ("R", 6)), (483, 180), (483, 333),
      (513, 333), (513, 288), ("RTC", 2))
# ---- sinyal kiri
route("IO33-pH",  BLU, ("E", ("L", 8)),  ("pH", 2))
route("IO34-TDS", BLU, ("E", ("L", 11)), ("TDS", 2))
route("IO14-FS-BAWAH", GRN, ("E", ("L", 4)), (279, 306), (243, 306), ("FSB", 0))
route("IO27-FS-ATAS",  GRN, ("E", ("L", 5)), (279, 360), (243, 360), ("FSA", 0))
route("IO32-TOMBOL",   GRN, ("E", ("L", 9)), (279, 414), (243, 414), ("BTN", 0))
route("IO26-BUZZER",   GRY, ("E", ("L", 6)), (342, 180), (342, 477),
      (270, 477), ("BZ", 2))
route("IO25-RELAY-IN", GRY, ("E", ("L", 7)), (333, 189), (333, 576),
      (207, 576), (207, 540), ("RLY", 0))
# ---- 5V (VIN) : pH '+' , relay VCC
route("5V-pH", RED, ("E", ("L", 0)), (288, 171), (261, 171), ("pH", 1))
route("5V-RELAY", RED, ("E", ("L", 0)), (324, 126), (324, 567),
      (216, 567), (216, 540), ("RLY", 1))
# ---- 3V3 kanan: DHT, DS, LCD, RTC + cabang kiri TDS & BUZZER
route("3V3", ORG, ("E", ("R", 0)), ("DHT", 0))
route("3V3", ORG, ("DHT", 0), (522, 162), (558, 162), (558, 216), (522, 216), ("DS", 0))
route("3V3", ORG, (558, 216), (558, 297), (531, 297), (531, 288), ("RTC", 4))
route("3V3", ORG, (558, 216), (648, 216), (648, 162), ("LCD", 1))
route("3V3", ORG, ("E", ("R", 0)), (459, 108), (468, 108), (468, 279),
      (261, 279), (261, 234), ("TDS", 1))
route("3V3", ORG, (261, 279), (261, 426), (285, 426),
      (285, 486), (261, 486), ("BZ", 1))
# ---- GND kanan: E.GND -> DHT -> DS -> LCD -> RTC
route("GND", BLK, ("E", ("R", 1)), (474, 123), (474, 105),
      (567, 105), (567, 144))
route("GND", BLK, ("DHT", 2), (567, 144), (567, 225), (540, 225), (540, 207), ("DS", 2))
route("GND", BLK, ("DS", 2), (639, 162), ("LCD", 0))
route("GND", BLK, ("LCD", 0), (639, 288), (540, 288), ("RTC", 5))
# ---- GND kiri: E.GND -> pH -> trunk -> TDS, FS x2, TOMBOL, BUZZER -> RELAY
route("GND", BLK, ("E", ("L", 1)), (297, 135), (297, 162), (252, 162), (252, 153), ("pH", 0))
route("GND", BLK, ("pH", 0), (225, 153), (225, 234), ("TDS", 0))
route("GND", BLK, (225, 234), (225, 315), (252, 315), (252, 297), ("FSB", 1))
route("GND", BLK, (225, 315), (225, 369), (252, 369), (252, 351), ("FSA", 1))
route("GND", BLK, (225, 369), (225, 423), (252, 423), (252, 405), ("BTN", 1))
route("GND", BLK, (225, 423), (225, 468), ("BZ", 0))
route("GND", BLK, (225, 468), (198, 468), (198, 558), (225, 558), (225, 540), ("RLY", 2))
# ---- pull-up
route("IO4-DHT22", GRN, ("R1", 0), ("DHT", 1))
route("3V3", ORG, ("R1", 1), (594, 216), (495, 216),
      (495, 144), ("DHT", 0))
route("IO18-DS18B20", PUR, ("R2", 0), ("DS", 1))
route("3V3", ORG, ("R2", 1), ("DS", 0))
# ---- sisi beban 12V (terisolasi)
route("12V-PSU+", RED, ("PSU", 0), (108, 549), (285, 549), (285, 522), ("RLY", "COM"))
route("12V-SOL+", RED, ("RLY", "NO"), (276, 513), (276, 612),
      (108, 612), (108, 603), ("SOL", 0))
route("12V-GND", BLK, ("D1", 1), ("SOL", 1))          # anoda A -> kaki (-)
route("12V-SOL+", RED, ("D1", 0), ("SOL", 0))          # kathode K -> kaki (+)
route("12V-GND", BLK, ("SOL", 1), (117, 633), (90, 633),
      (90, 558), (117, 558), (117, 522), ("PSU", 1))   # kaki (-) -> PSU GND

# titik pin yang diakhiri route dgn pin ref — ditarik juga dari ujung route
def is_pin_ref(p):
    return (isinstance(p, tuple) and len(p) == 2 and isinstance(p[0], str)
            and p[0] in INSTANCES)

def resolve(p):
    if is_pin_ref(p):
        return pt(p[0], p[1])
    return tuple(p)

# ============================================================ BUILD .fz
# route dgn nama berbeda boleh berbagi titik bila fisik net sama
PHYS = {"5V-pH": "5V", "5V-RELAY": "5V"}
def phys(n):
    return PHYS.get(n, n)

def build():
    inst_order = list(INSTANCES.keys())
    model = {}
    for i, k in enumerate(inst_order):
        model[k] = 100 + i
    next_wire = [300]

    # atribut per titik: (x,y) -> daftar (kind, modelIndex, connectorId)
    # kind: 'part' | 'wire'
    at_point = {}
    wire_defs = []   # (net, color, p_from, p_to, from_ref, to_ref)

    for net, color, pts in NETS:
        rp = [resolve(p) for p in pts]
        for a, b in zip(rp, rp[1:]):
            if a == b:
                continue
            wid = next_wire[0]; next_wire[0] += 1
            wire_defs.append((net, color, a, b, wid))
            for p, ref in ((a, pts[0]), (b, pts[-1])):
                pass
        # catat attachment utk tiap titik
        for idx, (a, b) in enumerate(zip(rp, rp[1:])):
            wid = 300 + sum(1 for _ in range(0))  # placeholder diisi nanti
    # (ulang dgn id tetap)
    wire_defs = []
    wid_seq = itertools.count(300)
    for net, color, pts in NETS:
        rp = [resolve(p) for p in pts]
        for a, b in zip(rp, rp[1:]):
            if a == b:
                continue
            wire_defs.append([net, color, a, b, next(wid_seq)])

    # attachment per titik: dari route awal/akhir (pin) + semua ujung wire
    attachments = {}   # point -> list of ('part', key, connId) | ('wire', wid, 0/1)
    def add(point, item):
        lst = attachments.setdefault(point, [])
        if item not in lst:
            lst.append(item)

    for net, color, pts in NETS:
        r = pts
        if not isinstance(r[0], tuple):
            continue
    # attachment dari struktur route
    for net, color, pts in NETS:
        first, last = pts[0], pts[-1]
        if is_pin_ref(first):
            k = first[0]
            add(resolve(first), ("part", k, f"connector{conn_index(k, first[1])}"))
        if is_pin_ref(last):
            k = last[0]
            add(resolve(last), ("part", k, f"connector{conn_index(k, last[1])}"))
    for net, color, a, b, wid in wire_defs:
        add(a, ("wire", wid, 0))
        add(b, ("wire", wid, 1))

    # validasi: tiap titik hanya milik 1 net; titik dgn >2 attachment = junction
    pt_net = {}
    for net, color, pts in NETS:
        for p in [resolve(x) for x in pts]:
            pt_net.setdefault(p, set()).add(phys(net))
    bad = {p: n for p, n in pt_net.items() if len(n) > 1}
    assert not bad, f"titik dipakai >1 net: {bad}"

    # emits
    out = []
    out.append('<?xml version="1.0" encoding="UTF-8"?>')
    out.append('<module fritzingVersion="1.0.8.2026-06-14.1.0.7b1-97-g2bb9ba47a" icon=".png">')
    out.append("  <project_properties>")
    out.append('    <simulator_animation_time_s value="5s"/>')
    out.append('    <simulator_number_of_steps value="400"/>')
    out.append('    <simulator_spice_options value=""/>')
    out.append('    <simulator_time_step_mode value="false"/>')
    out.append('    <simulator_time_step_s value="1us"/>')
    out.append("  </project_properties>")
    out.append("  <views>")
    out.append('    <view name="breadboardView" backgroundColor="#ffffff" gridSize="0.1in" showGrid="1" alignToGrid="0" viewFromBelow="0" colorWiresByLength="0"/>')
    out.append('    <view name="schematicView" backgroundColor="#ffffff" gridSize="0.1in" showGrid="1" alignToGrid="1" viewFromBelow="0"/>')
    out.append('    <view name="pcbView" backgroundColor="#333333" gridSize="0.05in" showGrid="1" alignToGrid="1" viewFromBelow="0" DRC_Keepout="0.01in" autorouteTraceWidth="24"/>')
    out.append("  </views>")
    out.append("  <instances>")

    # kumpulkan connects per (modelIndex, connectorId)
    connects = {}   # (mi, cid) -> list of (cid_t, mi_t, layer)
    for point, items in attachments.items():
        for a, b in itertools.combinations(items, 2):
            if a[0] == "part" and b[0] == "part":
                continue  # dua pin tidak pernah dihubungi langsung
            (ca, cb) = (a, b)
            for src, dst in ((a, b), (b, a)):
                layer = "breadboardWire" if dst[0] == "wire" else "breadboardbreadboard"
                if src[0] == "part":
                    key = (model[src[1]], src[2])
                else:
                    key = (src[1], "connector0" if src[2] == 0 else "connector1")
                connects.setdefault(key, []).append((dst[2] if dst[0] == "part"
                    else ("connector0" if dst[2] == 0 else "connector1"),
                    model[dst[1]] if dst[0] == "part" else dst[1], layer))

    # instance part
    for k in inst_order:
        mid, title, x, y, props = INSTANCES[k]
        out.append(f'    <instance moduleIdRef="{mid}" modelIndex="{model[k]}" path="{mid}.fzp">')
        for pn, pv in props.items():
            out.append(f'      <property name="{html.escape(pn)}" value="{html.escape(pv)}"/>')
        out.append(f"      <title>{html.escape(title)}</title>")
        out.append("      <views>")
        out.append('        <breadboardView layer="breadboard">')
        out.append(f'          <geometry z="2.50019" x="{x}" y="{y}"/>')
        conns = [(c, cs) for (mi, c), cs in connects.items() if mi == model[k]]
        if conns:
            out.append("          <connectors>")
            for cid, cs in sorted(conns, key=lambda t: int(t[0].replace("connector", ""))):
                out.append(f'            <connector connectorId="{cid}" layer="breadboard">')
                out.append('              <geometry x="0" y="0"/>')
                out.append("              <connects>")
                for tcid, tmi, layer in cs:
                    out.append(f'                <connect connectorId="{tcid}" modelIndex="{tmi}" layer="{layer}"/>')
                out.append("              </connects>")
                out.append("            </connector>")
            out.append("          </connectors>")
        out.append("        </breadboardView>")
        out.append("      </views>")
        out.append("    </instance>")

    # instance wire
    for net, color, a, b, wid in wire_defs:
        dx, dy = b[0] - a[0], b[1] - a[1]
        out.append(f'    <instance moduleIdRef="WireModuleID" modelIndex="{wid}" path="wire.fzp">')
        out.append(f"      <title>{html.escape(net)}</title>")
        out.append("      <views>")
        out.append('        <breadboardView layer="breadboardWire">')
        out.append(f'          <geometry z="3.50014" x="{a[0]}" y="{a[1]}" x1="0" y1="0" x2="{dx}" y2="{dy}" wireFlags="64"/>')
        out.append(f'          <wireExtras mils="22.2222" color="{color}" opacity="1" banded="0"/>')
        out.append("          <connectors>")
        for end in (0, 1):
            cid = f"connector{end}"
            key = (wid, cid)
            cs = connects.get(key, [])
            out.append(f'            <connector connectorId="{cid}" layer="breadboardWire">')
            out.append('              <geometry x="0" y="0"/>')
            if cs:
                out.append("              <connects>")
                for tcid, tmi, layer in cs:
                    out.append(f'                <connect connectorId="{tcid}" modelIndex="{tmi}" layer="{layer}"/>')
                out.append("              </connects>")
            else:
                out.append("              <connects/>")
            out.append("            </connector>")
        out.append("          </connectors>")
        out.append("        </breadboardView>")
        out.append("      </views>")
        out.append("    </instance>")

    out.append("  </instances>")
    out.append("</module>")
    return "\n".join(out) + "\n", wire_defs, model, attachments, connects

def conn_index(key, pin):
    if key == "E":
        col, i = pin
        return i if col == "L" else 15 + i
    if key == "RLY":
        if isinstance(pin, str):
            return 3 + {"NO": 0, "COM": 1, "NC": 2}[pin]
        return pin
    return pin

# ============================================================ VALIDASI
def validate(fz_text, wire_defs, model, attachments, connects):
    import xml.dom.minidom as md
    md.parseString(fz_text)
    errors = []
    # 1. modelIndex unik
    assert len(set(model.values())) == len(model)
    wids = [w[4] for w in wire_defs]
    assert len(wids) == len(set(wids))
    # 2. moduleIdRef ada di parts_build
    for mid in set(re.findall(r'moduleIdRef="([^"]+)"', fz_text)):
        if mid == "WireModuleID":
            continue
        if not os.path.exists(os.path.join(PARTS, mid + ".fzp")):
            errors.append(f"fzp hilang: {mid}")
    # 3. connectorId valid di fzp
    for (mi, cid), cs in connects.items():
        if cid not in known_conns_for(mi):
            errors.append(f"connector {cid} tdk ada utk modelIndex {mi}")
        for tcid, tmi, layer in cs:
            if tmi in model.values():
                if tcid not in known_conns_for(tmi):
                    errors.append(f"target connector {tcid} tdk ada utk {tmi}")
            if layer not in ("breadboardWire", "breadboardbreadboard"):
                errors.append(f"layer salah: {layer}")
    # 4. simetri connects
    for (mi, cid), cs in connects.items():
        for tcid, tmi, layer in cs:
            rev = [(c, m) for c, m, _ in connects.get((tmi, tcid), [])]
            if (cid, mi) not in rev:
                errors.append(f"asimetris: {mi}.{cid} -> {tmi}.{tcid} tanpa balasan")
    # 5. tiap wire: connector0 di a, connector1 di b (sesuai geometry)
    for net, color, a, b, wid in wire_defs:
        if (wid, "connector0") not in connects and (wid, "connector1") not in connects:
            errors.append(f"wire {wid} tanpa koneksi")
    # 6. crossing vs bodi part (hanya ujung wire-pin yg diizinkan di dalam bodi)
    def seg_box(a, b, box):
        x0, y0, w, h = box
        x1, y1, x2, y2 = min(a[0], b[0]), min(a[1], b[1]), max(a[0], b[0]), max(a[1], b[1])
        return not (x2 < x0 or x1 > x0 + w or y2 < y0 or y1 > y0 + h)
    def seg_intersects_box(a, b, box, pad=0):
        # approximation: sampling
        x0, y0, w, h = box[0] + pad, box[1] + pad, box[2] - 2 * pad, box[3] - 2 * pad
        n = max(2, int(abs(b[0] - a[0]) + abs(b[1] - a[1])) // 2)
        for i in range(n + 1):
            t = i / n
            x = a[0] + (b[0] - a[0]) * t
            y = a[1] + (b[1] - a[1]) * t
            if x0 <= x <= x0 + w and y0 <= y <= y0 + h:
                # izinkan jika dekat ujung (pendekatan pin, termasuk pin
                # yang berada di dalam bodi part sendiri, mis. pin header ESP32)
                if (abs(x - a[0]) + abs(y - a[1]) < 13 or
                        abs(x - b[0]) + abs(y - b[1]) < 13):
                    continue
                return (round(x, 1), round(y, 1))
        return None
    warns = []
    for net, color, a, b, wid in wire_defs:
        for bk, box in BODIES.items():
            hit = seg_intersects_box(a, b, box, pad=4)
            if hit:
                warns.append(f"wire {wid} ({net}) menembus bodi {bk} di {hit}")
    # 7. kabel melewati pin part lain (bukan ujungnya sendiri) — hindari salah baca
    all_pins = {}
    for k, (mid, *_r) in INSTANCES.items():
        if k == "E":
            for i in range(15):
                all_pins[pt("E", ("L", i))] = f"{k}.L{i}"
                all_pins[pt("E", ("R", i))] = f"{k}.R{i}"
        elif k == "RLY":
            for i in range(3):
                all_pins[pt("RLY", i)] = f"{k}.in{i}"
            for s in ("NO", "COM", "NC"):
                all_pins[pt("RLY", s)] = f"{k}.{s}"
        else:
            n = {"RTC": 6, "LCD": 4}.get(k, {"FSB": 2, "FSA": 2, "BTN": 2,
                 "D1": 2, "R1": 2, "R2": 2, "PSU": 2, "SOL": 2}.get(k, 3))
            for i in range(n):
                all_pins[pt(k, i)] = f"{k}.{i}"
    def dist_to_seg(p, a, b):
        n = max(2, int(abs(b[0]-a[0]) + abs(b[1]-a[1])) // 2)
        best = 1e9
        for i in range(n + 1):
            t = i / n
            x = a[0] + (b[0]-a[0]) * t
            y = a[1] + (b[1]-a[1]) * t
            best = min(best, abs(p[0]-x) + abs(p[1]-y))
        return best
    # net per pin: pin dgn net sama dgn kabel tdk dianggap "dilewati"
    pin_nets = {}
    for net, color, pts in NETS:
        for p in pts:
            if is_pin_ref(p):
                pin_nets.setdefault(resolve(p), set()).add(phys(net))
    own_end = {}
    for net, color, a, b, wid in wire_defs:
        own_end.setdefault(net, set()).update([a, b])
    for net, color, a, b, wid in wire_defs:
        for p, name in all_pins.items():
            if p in (a, b):
                continue
            if phys(net) in pin_nets.get(p, set()):
                continue
            if dist_to_seg(p, a, b) < 4:
                warns.append(f"wire {wid} ({net}) melewati pin {name} di {p}")
    # 8. ujung kabel net A menyentuh tengah kabel net B (T-tak-sengaja)
    for net_a, color, ea, eb, wida in wire_defs:
        for net_b, color2, fa, fb, widb in wire_defs:
            if phys(net_a) == phys(net_b) or wida == widb:
                continue
            for e in (ea, eb):
                if e in (fa, fb):
                    continue
                if dist_to_seg(e, fa, fb) < 3:
                    warns.append(
                        f"wire {wida} ({net_a}) ujung {e} menyentuh wire {widb} ({net_b})")
    return errors, warns

_kc = {}
def known_conns_for(mi):
    if mi >= 300:   # wire: selalu connector0/connector1
        return {"connector0", "connector1"}
    if mi in _kc:
        return _kc[mi]
    for k, (mid, *_rest) in INSTANCES.items():
        pass
    return _kc.setdefault(mi, _load_conns(mi))

def _load_conns(mi):
    # cari moduleId dgn modelIndex
    for k, v in INSTANCES.items():
        pass
    # mapping model -> mid
    keys = list(INSTANCES.keys())
    for i, k in enumerate(keys):
        if 100 + i == mi:
            mid = INSTANCES[k][0]
            t = open(os.path.join(PARTS, mid + ".fzp")).read()
            return set(re.findall(r'<connector id="(connector\d+)"', t))
    return set()

# ============================================================ PREVIEW
def build_preview(fz_text, wire_defs):
    W, H = 760, 700
    parts_svg = []
    for k, (mid, title, x, y, props) in INSTANCES.items():
        src = open(os.path.join(PARTS, f"{mid}_breadboard.svg")).read()
        inner = re.search(r'<g id="breadboard">(.*)</g>\s*</svg>', src, re.S).group(1)
        parts_svg.append(f'<g transform="translate({x},{y})">{inner}</g>')
        parts_svg.append(f'<text x="{x+4}" y="{y-4}" font-size="10" font-weight="bold" fill="#0645ad">{title}</text>')
    wires_svg = []
    for net, color, a, b, wid in wire_defs:
        wires_svg.append(f'<line x1="{a[0]}" y1="{a[1]}" x2="{b[0]}" y2="{b[1]}" '
                         f'stroke="{color}" stroke-width="2.4" stroke-linecap="round"/>')
    dots = []
    for point, items in attachments_global.items():
        if len(items) >= 3 or any(i[0] == "part" for i in items):
            dots.append(f'<circle cx="{point[0]}" cy="{point[1]}" r="3" fill="none" stroke="#d50000" stroke-width="1"/>')
    doc = (f'<?xml version="1.0" encoding="UTF-8"?>\n'
           f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" '
           f'viewBox="0 0 {W} {H}">\n<rect width="{W}" height="{H}" fill="#ffffff"/>\n'
           + "\n".join(wires_svg) + "\n" + "\n".join(parts_svg) + "\n"
           + "\n".join(dots) + "\n</svg>\n")
    open(OUT_PREVIEW, "w").write(doc)

attachments_global = None

if __name__ == "__main__":
    fz, wire_defs, model, attachments, connects = build()
    attachments_global = attachments
    errors, warns = validate(fz, wire_defs, model, attachments, connects)
    print(f"wires: {len(wire_defs)}, part instances: {len(INSTANCES)}, "
          f"junction points: {sum(1 for v in attachments.values() if len(v) >= 3)}")
    if errors:
        print("ERRORS:")
        for e in errors:
            print("  ", e)
    else:
        print("validasi: OK (XML, modelIndex, connectorId, simetri connects)")
    if warns:
        print("WARNINGS kabel menembus bodi:")
        for w in warns:
            print("  ", w)
    open(OUT_FZ, "w").write(fz)

    # ==== zip .fzz: sketch + part ter-embed (part.<fzp>, svg.<view>.<file>) ====
    names = sorted({mid for mid, *_ in INSTANCES.values()})
    with zipfile.ZipFile(OUT_FZZ, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("harvesthouse_breadboard.fz", fz)
        for mid in names:
            z.write(os.path.join(PARTS, f"{mid}.fzp"), f"part.{mid}.fzp")
            for view in ("breadboard", "schematic", "pcb", "icon"):
                z.write(os.path.join(PARTS, f"{mid}_{view}.svg"),
                        f"svg.{view}.{mid}_{view}.svg")
    print("ditulis:", OUT_FZ, "|", OUT_FZZ)

    build_preview(fz, wire_defs)
    print("preview:", OUT_PREVIEW)
