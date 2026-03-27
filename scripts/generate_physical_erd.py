import argparse
import os
import re
from dataclasses import dataclass, field
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


CREATE_TABLE_RE = re.compile(
    r"CREATE TABLE\s+`(?P<name>[^`]+)`\s*\((?P<body>.*?)\)\s*ENGINE=",
    re.IGNORECASE | re.DOTALL,
)
ALTER_TABLE_RE = re.compile(
    r"ALTER TABLE\s+`(?P<name>[^`]+)`\s*(?P<body>.*?);",
    re.IGNORECASE | re.DOTALL,
)
COLUMN_RE = re.compile(r"^\s*`(?P<name>[^`]+)`\s+(?P<type>.+?)\s*$")
PRIMARY_KEY_RE = re.compile(r"ADD PRIMARY KEY\s*\((?P<cols>[^)]+)\)", re.IGNORECASE)
UNIQUE_KEY_RE = re.compile(r"ADD UNIQUE KEY\s+`[^`]+`\s*\((?P<cols>[^)]+)\)", re.IGNORECASE)
FOREIGN_KEY_RE = re.compile(
    r"FOREIGN KEY\s*\(`(?P<column>[^`]+)`\)\s+REFERENCES\s+`(?P<ref_table>[^`]+)`\s*\(`(?P<ref_column>[^`]+)`\)",
    re.IGNORECASE,
)

CANVAS_WIDTH = 1760
CANVAS_HEIGHT = 1120
LEFT_MARGIN = 40
TOP_MARGIN = 36
ROW_HEIGHT = 26
HEADER_HEIGHT = 28
FOOTER_HEIGHT = 22

COLORS = {
    "bg": "#f1f3f6",
    "table_border": "#8e99a7",
    "table_fill": "#ffffff",
    "table_header": "#dde5ee",
    "row_alt": "#f7f9fb",
    "text": "#1d2630",
    "muted": "#677381",
    "pk": "#1f65d6",
    "fk": "#2ca24f",
    "line": "#4d7fb8",
    "line2": "#5aa65c",
    "line3": "#c3a400",
    "label_fill": "#ffffff",
    "label_stroke": "#7f6cf2",
    "label_text": "#5a43db",
    "note_fill": "#fff6d8",
    "note_stroke": "#bda33f",
    "note_text": "#715f10",
}

DISPLAY_COLUMNS = {
    "danh_muc_dich_vu": ["id", "ten_dich_vu", "mo_ta", "trang_thai", "created_at"],
    "chat_magic": ["id", "user_id", "guest_token", "sender", "text", "created_at"],
    "users": ["id", "name", "email", "phone", "role", "is_active", "created_at"],
    "ho_so_tho": ["id", "user_id", "cccd", "kinh_nghiem", "ban_kinh_phuc_vu", "trang_thai_duyet", "danh_gia_trung_binh"],
    "don_dat_lich_dich_vu": ["id", "don_dat_lich_id", "dich_vu_id", "created_at"],
    "thanh_toan": ["id", "don_dat_lich_id", "so_tien", "phuong_thuc", "trang_thai", "created_at"],
    "danh_gia": ["id", "don_dat_lich_id", "nguoi_danh_gia_id", "nguoi_bi_danh_gia_id", "so_sao", "nhan_xet", "created_at"],
    "tho_dich_vu": ["id", "user_id", "dich_vu_id", "created_at"],
    "don_dat_lich": ["id", "khach_hang_id", "tho_id", "dich_vu_id", "loai_dat_lich", "thoi_gian_hen", "trang_thai", "tong_tien"],
}


@dataclass
class Column:
    name: str
    type: str
    not_null: bool


@dataclass
class Table:
    name: str
    columns: list[Column] = field(default_factory=list)
    primary_keys: set[str] = field(default_factory=set)
    unique_sets: list[tuple[str, ...]] = field(default_factory=list)
    foreign_keys: set[str] = field(default_factory=set)


@dataclass
class Relation:
    table: str
    column: str
    ref_table: str
    ref_column: str

    @property
    def key(self) -> str:
        return f"{self.table}.{self.column}"


LAYOUT = {
    "danh_muc_dich_vu": {"x": 40, "y": 48},
    "chat_magic": {"x": 40, "y": 258},
    "users": {"x": 40, "y": 498},
    "ho_so_tho": {"x": 360, "y": 48},
    "tho_dich_vu": {"x": 360, "y": 468},
    "don_dat_lich_dich_vu": {"x": 700, "y": 510},
    "danh_gia": {"x": 700, "y": 760},
    "don_dat_lich": {"x": 1040, "y": 118},
    "thanh_toan": {"x": 1380, "y": 510},
}

ROUTES = {
    "chat_magic.user_id": {"parent_side": "right", "child_side": "left", "lane": 334, "color": "line2"},
    "ho_so_tho.user_id": {"parent_side": "right", "child_side": "left", "lane": 334, "color": "line2"},
    "tho_dich_vu.user_id": {"parent_side": "right", "child_side": "left", "lane": 334, "color": "line2"},
    "danh_gia.nguoi_danh_gia_id": {"parent_side": "right", "child_side": "left", "lane": 674, "color": "line2"},
    "danh_gia.nguoi_bi_danh_gia_id": {"parent_side": "right", "child_side": "left", "lane": 654, "color": "line2"},
    "tho_dich_vu.dich_vu_id": {"parent_side": "right", "child_side": "left", "lane": 334, "color": "line3"},
    "don_dat_lich_dich_vu.dich_vu_id": {"parent_side": "right", "child_side": "left", "lane": 674, "color": "line3"},
    "don_dat_lich.dich_vu_id": {"parent_side": "right", "child_side": "left", "lane": 1000, "color": "line3"},
    "don_dat_lich.khach_hang_id": {"parent_side": "right", "child_side": "left", "lane": 820, "color": "line2"},
    "don_dat_lich.tho_id": {"parent_side": "right", "child_side": "left", "lane": 920, "color": "line"},
    "don_dat_lich_dich_vu.don_dat_lich_id": {"parent_side": "left", "child_side": "right", "lane": 1010, "color": "line"},
    "danh_gia.don_dat_lich_id": {"parent_side": "left", "child_side": "right", "lane": 990, "color": "line"},
    "thanh_toan.don_dat_lich_id": {"parent_side": "right", "child_side": "left", "lane": 1350, "color": "line"},
}


def load_font(size: int, bold: bool = False):
    candidates = []
    if bold:
        candidates += ["C:/Windows/Fonts/arialbd.ttf", "C:/Windows/Fonts/segoeuib.ttf"]
    candidates += ["C:/Windows/Fonts/arial.ttf", "C:/Windows/Fonts/segoeui.ttf"]
    for path in candidates:
        if os.path.exists(path):
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def split_lines(body: str) -> list[str]:
    return [line.strip().rstrip(",") for line in body.splitlines() if line.strip()]


def parse_sql(sql: str) -> tuple[dict[str, Table], list[Relation]]:
    tables: dict[str, Table] = {}
    relations: list[Relation] = []

    for match in CREATE_TABLE_RE.finditer(sql):
        table = Table(name=match.group("name"))
        for line in split_lines(match.group("body")):
            col_match = COLUMN_RE.match(line)
            if not col_match:
                continue
            raw_type = col_match.group("type")
            table.columns.append(
                Column(
                    name=col_match.group("name"),
                    type=short_type(raw_type),
                    not_null="NOT NULL" in raw_type.upper(),
                )
            )
        tables[table.name] = table

    for match in ALTER_TABLE_RE.finditer(sql):
        table = tables.get(match.group("name"))
        if not table:
            continue
        body = match.group("body")

        pk = PRIMARY_KEY_RE.search(body)
        if pk:
            table.primary_keys.update(re.findall(r"`([^`]+)`", pk.group("cols")))

        for unique_match in UNIQUE_KEY_RE.finditer(body):
            cols = tuple(re.findall(r"`([^`]+)`", unique_match.group("cols")))
            table.unique_sets.append(cols)

        for fk in FOREIGN_KEY_RE.finditer(body):
            column = fk.group("column")
            table.foreign_keys.add(column)
            relations.append(
                Relation(
                    table=table.name,
                    column=column,
                    ref_table=fk.group("ref_table"),
                    ref_column=fk.group("ref_column"),
                )
            )

    return tables, relations


def short_type(raw_type: str) -> str:
    raw = raw_type.lower()
    for prefix, short in [
        ("bigint", "bigint"),
        ("varchar", "varchar"),
        ("decimal", "decimal"),
        ("enum", "enum"),
        ("tinyint", "tinyint"),
        ("timestamp", "timestamp"),
        ("datetime", "datetime"),
        ("date", "date"),
        ("json", "json"),
        ("longtext", "longtext"),
        ("text", "text"),
    ]:
        if raw.startswith(prefix):
            return short
    return raw_type.split()[0]


def filter_related(tables: dict[str, Table], relations: list[Relation]) -> tuple[dict[str, Table], list[Relation]]:
    names = set()
    for rel in relations:
        names.add(rel.table)
        names.add(rel.ref_table)
    related_tables = {name: tables[name] for name in names}
    return related_tables, relations


def text_width(draw: ImageDraw.ImageDraw, text: str, font) -> int:
    box = draw.textbbox((0, 0), text, font=font)
    return box[2] - box[0]


def visible_columns(table: Table) -> list[Column]:
    preferred = DISPLAY_COLUMNS.get(table.name, [])
    selected: list[Column] = []
    seen: set[str] = set()

    def push(name: str):
        if name in seen:
            return
        column = next((col for col in table.columns if col.name == name), None)
        if column is None:
            return
        selected.append(column)
        seen.add(name)

    for name in sorted(table.primary_keys):
        push(name)
    for name in preferred:
        push(name)
    for name in sorted(table.foreign_keys):
        push(name)

    return selected


def build_geometry(tables: dict[str, Table], draw: ImageDraw.ImageDraw, header_font, row_font):
    geometry = {}
    for name, table in tables.items():
        x = LAYOUT[name]["x"]
        y = LAYOUT[name]["y"]
        header_text = f"find_workers {name}"
        shown_columns = visible_columns(table)
        max_row = max(
            text_width(draw, f"{col.name} : {col.type}", row_font)
            for col in shown_columns
        )
        width = max(280, text_width(draw, header_text, header_font) + 28, max_row + 54)
        hidden_count = max(0, len(table.columns) - len(shown_columns))
        height = HEADER_HEIGHT + len(shown_columns) * ROW_HEIGHT + 2 + (FOOTER_HEIGHT if hidden_count else 0)
        geometry[name] = {"x": x, "y": y, "w": width, "h": height}
    return geometry


def row_center(geometry, table_name: str, column_name: str, tables: dict[str, Table]) -> int:
    g = geometry[table_name]
    idx = next(i for i, col in enumerate(visible_columns(tables[table_name])) if col.name == column_name)
    return g["y"] + HEADER_HEIGHT + idx * ROW_HEIGHT + ROW_HEIGHT // 2


def port(geometry, table_name: str, column_name: str, side: str, tables: dict[str, Table]) -> tuple[int, int]:
    g = geometry[table_name]
    y = row_center(geometry, table_name, column_name, tables)
    x = g["x"] if side == "left" else g["x"] + g["w"]
    return x, y


def relation_cardinality(relation: Relation, tables: dict[str, Table]) -> tuple[str, str]:
    child_table = tables[relation.table]
    is_unique = any(cols == (relation.column,) for cols in child_table.unique_sets) or child_table.primary_keys == {relation.column}
    if is_unique:
        return "(1,1)", "(1,1)"
    return "(1,n)", "(1,1)"


def bridge_tables(tables: dict[str, Table], relations: list[Relation]) -> list[tuple[str, str, str]]:
    by_table: dict[str, list[Relation]] = {}
    for rel in relations:
        by_table.setdefault(rel.table, []).append(rel)

    derived = []
    for table_name, rels in by_table.items():
        if len(rels) != 2:
            continue
        table = tables[table_name]
        extra = [
            col.name
            for col in table.columns
            if col.name not in table.primary_keys
            and col.name not in {rels[0].column, rels[1].column, "created_at", "updated_at"}
        ]
        if extra:
            continue
        derived.append((rels[0].ref_table, rels[1].ref_table, table_name))
    return derived


def draw_table(draw: ImageDraw.ImageDraw, table: Table, g, header_font, row_font):
    x, y, w, h = g["x"], g["y"], g["w"], g["h"]
    draw.rectangle([x, y, x + w, y + h], fill=COLORS["table_fill"], outline=COLORS["table_border"], width=1)
    draw.rectangle([x, y, x + w, y + HEADER_HEIGHT], fill=COLORS["table_header"], outline=COLORS["table_border"], width=1)
    draw.text((x + 8, y + 5), f"find_workers {table.name}", fill=COLORS["text"], font=header_font)

    icon_x = x + 8
    shown_columns = visible_columns(table)
    for idx, col in enumerate(shown_columns):
        row_y = y + HEADER_HEIGHT + idx * ROW_HEIGHT
        if idx % 2 == 1:
            draw.rectangle([x + 1, row_y, x + w - 1, row_y + ROW_HEIGHT], fill=COLORS["row_alt"])
        draw.line([x, row_y, x + w, row_y], fill="#d8dee6", width=1)

        if col.name in table.primary_keys:
            draw.rectangle([icon_x, row_y + 8, icon_x + 8, row_y + 16], fill=COLORS["pk"], outline=COLORS["pk"])
        elif col.name in table.foreign_keys:
            draw.ellipse([icon_x, row_y + 8, icon_x + 8, row_y + 16], fill=COLORS["fk"], outline=COLORS["fk"])
        else:
            draw.rectangle([icon_x + 1, row_y + 9, icon_x + 7, row_y + 15], fill="#ffffff", outline="#8893a1")

        draw.text((x + 22, row_y + 5), f"{col.name} : {col.type}", fill=COLORS["text"], font=row_font)

    hidden_count = max(0, len(table.columns) - len(shown_columns))
    if hidden_count:
        footer_y = y + HEADER_HEIGHT + len(shown_columns) * ROW_HEIGHT
        draw.line([x, footer_y, x + w, footer_y], fill="#d8dee6", width=1)
        draw.text((x + 8, footer_y + 4), f"+ {hidden_count} cot khac", fill=COLORS["muted"], font=row_font)


def draw_card_box(draw: ImageDraw.ImageDraw, center: tuple[int, int], text: str, font):
    w = text_width(draw, text, font) + 14
    h = 20
    x = center[0] - w // 2
    y = center[1] - h // 2
    draw.rounded_rectangle(
        [x, y, x + w, y + h],
        radius=8,
        fill=COLORS["label_fill"],
        outline=COLORS["label_stroke"],
        width=2,
    )
    draw.text((x + 7, y + 3), text, fill=COLORS["label_text"], font=font)


def draw_relation(draw: ImageDraw.ImageDraw, relation: Relation, tables: dict[str, Table], geometry, card_font):
    config = ROUTES[relation.key]
    color = COLORS[config["color"]]
    parent_side = config["parent_side"]
    child_side = config["child_side"]
    lane_x = config["lane"]

    start = port(geometry, relation.ref_table, relation.ref_column, parent_side, tables)
    end = port(geometry, relation.table, relation.column, child_side, tables)
    points = [start, (lane_x, start[1]), (lane_x, end[1]), end]

    draw.line(points, fill=color, width=2, joint="curve")

    parent_card, child_card = relation_cardinality(relation, tables)
    start_label = ((start[0] + points[1][0]) // 2, start[1] - 12)
    end_label = ((end[0] + points[2][0]) // 2, end[1] - 12)
    draw_card_box(draw, start_label, parent_card, card_font)
    draw_card_box(draw, end_label, child_card, card_font)


def draw_bridge_notes(draw: ImageDraw.ImageDraw, derived_relations: list[tuple[str, str, str]], note_font):
    width = 460
    x = CANVAS_WIDTH - width - 40
    y = CANVAS_HEIGHT - (50 + len(derived_relations) * 30)
    height = 30 + len(derived_relations) * 30
    draw.rounded_rectangle(
        [x, y, x + width, y + height],
        radius=10,
        fill=COLORS["note_fill"],
        outline=COLORS["note_stroke"],
        width=2,
    )
    draw.text((x + 12, y + 8), "Quan he logic N-N duoc suy ra tu bang noi", fill=COLORS["note_text"], font=note_font)
    for idx, (left, right, bridge) in enumerate(derived_relations):
        text = f"{left} <-> {right} : (n,n) qua {bridge}"
        draw.text((x + 12, y + 36 + idx * 28), text, fill=COLORS["note_text"], font=note_font)


def render_png(output_path: Path, tables: dict[str, Table], relations: list[Relation]):
    image = Image.new("RGB", (CANVAS_WIDTH, CANVAS_HEIGHT), COLORS["bg"])
    draw = ImageDraw.Draw(image)
    header_font = load_font(13, bold=True)
    row_font = load_font(12)
    card_font = load_font(11, bold=True)
    title_font = load_font(16, bold=True)
    note_font = load_font(12)

    geometry = build_geometry(tables, draw, header_font, row_font)

    draw.text((LEFT_MARGIN, TOP_MARGIN - 24), "ERD vat ly - cac bang co quan he", fill=COLORS["text"], font=title_font)

    for relation in relations:
        draw_relation(draw, relation, tables, geometry, card_font)

    for name in LAYOUT:
        if name in tables:
            draw_table(draw, tables[name], geometry[name], header_font, row_font)

    draw_bridge_notes(draw, bridge_tables(tables, relations), note_font)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    image.save(output_path)


def svg_escape(text: str) -> str:
    return (
        text.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "&quot;")
    )


def svg_text(x: int, y: int, text: str, size: int = 12, weight: str = "400", fill: str | None = None) -> str:
    fill = fill or COLORS["text"]
    return f'<text x="{x}" y="{y}" font-family="Arial, Segoe UI, sans-serif" font-size="{size}" font-weight="{weight}" fill="{fill}">{svg_escape(text)}</text>'


def svg_relation(points: list[tuple[int, int]], color: str) -> str:
    pts = " ".join(f"{x},{y}" for x, y in points)
    return f'<polyline points="{pts}" fill="none" stroke="{color}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />'


def svg_card_box(center: tuple[int, int], text: str) -> str:
    width = len(text) * 7 + 14
    height = 20
    x = center[0] - width // 2
    y = center[1] - height // 2
    return (
        f'<rect x="{x}" y="{y}" rx="8" ry="8" width="{width}" height="{height}" '
        f'fill="{COLORS["label_fill"]}" stroke="{COLORS["label_stroke"]}" stroke-width="2" />'
        + svg_text(x + 7, y + 14, text, 11, "700", COLORS["label_text"])
    )


def render_svg(output_path: Path, tables: dict[str, Table], relations: list[Relation]):
    dummy = Image.new("RGB", (10, 10), COLORS["bg"])
    geometry = build_geometry(tables, ImageDraw.Draw(dummy), load_font(13, True), load_font(12))
    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{CANVAS_WIDTH}" height="{CANVAS_HEIGHT}" viewBox="0 0 {CANVAS_WIDTH} {CANVAS_HEIGHT}">',
        f'<rect width="{CANVAS_WIDTH}" height="{CANVAS_HEIGHT}" fill="{COLORS["bg"]}" />',
        svg_text(LEFT_MARGIN, TOP_MARGIN - 8, "ERD vat ly - cac bang co quan he", 16, "700"),
    ]

    for relation in relations:
        config = ROUTES[relation.key]
        color = COLORS[config["color"]]
        start = port(geometry, relation.ref_table, relation.ref_column, config["parent_side"], tables)
        end = port(geometry, relation.table, relation.column, config["child_side"], tables)
        points = [start, (config["lane"], start[1]), (config["lane"], end[1]), end]
        parts.append(svg_relation(points, color))
        parent_card, child_card = relation_cardinality(relation, tables)
        parts.append(svg_card_box(((start[0] + points[1][0]) // 2, start[1] - 12), parent_card))
        parts.append(svg_card_box(((end[0] + points[2][0]) // 2, end[1] - 12), child_card))

    for name in LAYOUT:
        if name not in tables:
            continue
        table = tables[name]
        g = geometry[name]
        x, y, w, h = g["x"], g["y"], g["w"], g["h"]
        shown_columns = visible_columns(table)
        parts += [
            f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{COLORS["table_fill"]}" stroke="{COLORS["table_border"]}" stroke-width="1" />',
            f'<rect x="{x}" y="{y}" width="{w}" height="{HEADER_HEIGHT}" fill="{COLORS["table_header"]}" stroke="{COLORS["table_border"]}" stroke-width="1" />',
            svg_text(x + 8, y + 18, f"find_workers {name}", 13, "700"),
        ]
        for idx, col in enumerate(shown_columns):
            row_y = y + HEADER_HEIGHT + idx * ROW_HEIGHT
            if idx % 2 == 1:
                parts.append(f'<rect x="{x + 1}" y="{row_y}" width="{w - 2}" height="{ROW_HEIGHT}" fill="{COLORS["row_alt"]}" />')
            parts.append(f'<line x1="{x}" y1="{row_y}" x2="{x + w}" y2="{row_y}" stroke="#d8dee6" stroke-width="1" />')
            if col.name in table.primary_keys:
                parts.append(f'<rect x="{x + 8}" y="{row_y + 8}" width="8" height="8" fill="{COLORS["pk"]}" />')
            elif col.name in table.foreign_keys:
                parts.append(f'<circle cx="{x + 12}" cy="{row_y + 12}" r="4" fill="{COLORS["fk"]}" />')
            else:
                parts.append(f'<rect x="{x + 9}" y="{row_y + 9}" width="6" height="6" fill="#ffffff" stroke="#8893a1" stroke-width="1" />')
            parts.append(svg_text(x + 22, row_y + 17, f"{col.name} : {col.type}", 12))
        hidden_count = max(0, len(table.columns) - len(shown_columns))
        if hidden_count:
            footer_y = y + HEADER_HEIGHT + len(shown_columns) * ROW_HEIGHT
            parts.append(f'<line x1="{x}" y1="{footer_y}" x2="{x + w}" y2="{footer_y}" stroke="#d8dee6" stroke-width="1" />')
            parts.append(svg_text(x + 8, footer_y + 17, f"+ {hidden_count} cot khac", 12, "400", COLORS["muted"]))

    derived = bridge_tables(tables, relations)
    width = 460
    x = CANVAS_WIDTH - width - 40
    y = CANVAS_HEIGHT - (50 + len(derived) * 30)
    height = 30 + len(derived) * 30
    parts += [
        f'<rect x="{x}" y="{y}" rx="10" ry="10" width="{width}" height="{height}" fill="{COLORS["note_fill"]}" stroke="{COLORS["note_stroke"]}" stroke-width="2" />',
        svg_text(x + 12, y + 20, "Quan he logic N-N duoc suy ra tu bang noi", 12, "400", COLORS["note_text"]),
    ]
    for idx, (left, right, bridge) in enumerate(derived):
        parts.append(svg_text(x + 12, y + 48 + idx * 28, f"{left} <-> {right} : (n,n) qua {bridge}", 12, "400", COLORS["note_text"]))

    parts.append("</svg>")
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text("\n".join(parts), encoding="utf-8")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("sql_file")
    parser.add_argument("--png", default="output/find_workers_erd_physical.png")
    parser.add_argument("--svg", default="output/find_workers_erd_physical.svg")
    args = parser.parse_args()

    sql = Path(args.sql_file).read_text(encoding="utf-8", errors="ignore")
    tables, relations = parse_sql(sql)
    tables, relations = filter_related(tables, relations)

    render_png(Path(args.png), tables, relations)
    render_svg(Path(args.svg), tables, relations)


if __name__ == "__main__":
    main()
