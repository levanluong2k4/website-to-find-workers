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
FOREIGN_KEY_RE = re.compile(
    r"FOREIGN KEY\s*\(`(?P<column>[^`]+)`\)\s+REFERENCES\s+`(?P<ref_table>[^`]+)`\s*\(`(?P<ref_column>[^`]+)`\)",
    re.IGNORECASE,
)


@dataclass
class Column:
    name: str
    type: str


@dataclass
class Table:
    name: str
    columns: list[Column] = field(default_factory=list)
    primary_keys: set[str] = field(default_factory=set)
    foreign_keys: list[str] = field(default_factory=list)


@dataclass
class Relation:
    table: str
    column: str
    ref_table: str
    ref_column: str


BOX_WIDTH = 320
ROW_HEIGHT = 26
HEADER_HEIGHT = 34
PADDING = 12
COLUMN_GAP = 120
ROW_GAP = 60

PALETTE = {
    "bg_top": "#f5f7fb",
    "bg_bottom": "#e8eef7",
    "title": "#16324f",
    "subtitle": "#4c647d",
    "box_fill": "#ffffff",
    "box_stroke": "#9fb3c8",
    "header_fill": "#1f4f82",
    "header_text": "#ffffff",
    "pk": "#0a7a45",
    "fk": "#c15814",
    "text": "#22384f",
    "muted": "#5e738c",
    "line": "#5a7795",
}


LAYOUT = {
    "users": (0, 0),
    "chat_magic": (1, 0),
    "ho_so_tho": (1, 1),
    "danh_muc_dich_vu": (2, 0),
    "tho_dich_vu": (2, 1),
    "don_dat_lich": (0, 2),
    "don_dat_lich_dich_vu": (1, 2),
    "thanh_toan": (2, 2),
    "danh_gia": (1, 3),
}


def split_lines(body: str) -> list[str]:
    return [line.strip().rstrip(",") for line in body.splitlines() if line.strip()]


def parse_tables(sql: str) -> dict[str, Table]:
    tables: dict[str, Table] = {}
    for match in CREATE_TABLE_RE.finditer(sql):
        name = match.group("name")
        body = match.group("body")
        table = Table(name=name)
        for line in split_lines(body):
            column_match = COLUMN_RE.match(line)
            if not column_match:
                continue
            table.columns.append(
                Column(name=column_match.group("name"), type=column_match.group("type"))
            )
        tables[name] = table

    for match in ALTER_TABLE_RE.finditer(sql):
        name = match.group("name")
        body = match.group("body")
        if name not in tables:
            continue
        table = tables[name]

        pk_match = PRIMARY_KEY_RE.search(body)
        if pk_match:
            pk_cols = re.findall(r"`([^`]+)`", pk_match.group("cols"))
            table.primary_keys.update(pk_cols)

        for fk_match in FOREIGN_KEY_RE.finditer(body):
            table.foreign_keys.append(fk_match.group("column"))

    return tables


def parse_relations(sql: str) -> list[Relation]:
    relations: list[Relation] = []
    for match in ALTER_TABLE_RE.finditer(sql):
        table = match.group("name")
        body = match.group("body")
        for fk_match in FOREIGN_KEY_RE.finditer(body):
            relations.append(
                Relation(
                    table=table,
                    column=fk_match.group("column"),
                    ref_table=fk_match.group("ref_table"),
                    ref_column=fk_match.group("ref_column"),
                )
            )
    return relations


def tables_with_relations(relations: list[Relation]) -> set[str]:
    names = set()
    for rel in relations:
        names.add(rel.table)
        names.add(rel.ref_table)
    return names


def prioritize_columns(table: Table) -> list[Column]:
    priority: list[str] = []
    priority.extend(sorted(table.primary_keys))
    priority.extend([name for name in table.foreign_keys if name not in priority])

    preferred = [
        "name",
        "role",
        "email",
        "ten_dich_vu",
        "trang_thai",
        "phuong_thuc",
        "so_tien",
        "thoi_gian_hen",
        "created_at",
    ]
    for name in preferred:
        if name not in priority and any(col.name == name for col in table.columns):
            priority.append(name)

    for column in table.columns:
        if column.name not in priority:
            priority.append(column.name)

    selected_names = priority[:6]
    return [column for name in selected_names for column in table.columns if column.name == name]


def box_height(table: Table) -> int:
    columns = prioritize_columns(table)
    hidden_count = max(0, len(table.columns) - len(columns))
    footer_height = 22 if hidden_count else 0
    return HEADER_HEIGHT + len(columns) * ROW_HEIGHT + PADDING + footer_height


def default_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    font_candidates = []
    if bold:
        font_candidates.extend(
            [
                "C:/Windows/Fonts/arialbd.ttf",
                "C:/Windows/Fonts/segoeuib.ttf",
            ]
        )
    font_candidates.extend(
        [
            "C:/Windows/Fonts/arial.ttf",
            "C:/Windows/Fonts/segoeui.ttf",
        ]
    )
    for candidate in font_candidates:
        if os.path.exists(candidate):
            return ImageFont.truetype(candidate, size=size)
    return ImageFont.load_default()


def build_layout(tables: dict[str, Table]) -> tuple[dict[str, tuple[int, int, int, int]], int, int]:
    positions: dict[str, tuple[int, int, int, int]] = {}
    col_width = BOX_WIDTH

    columns = {}
    rows = {}
    for name, (col_idx, row_idx) in LAYOUT.items():
        columns[col_idx] = col_width
        rows[row_idx] = max(rows.get(row_idx, 0), box_height(tables[name]))

    x_positions = {}
    current_x = 80
    for col_idx in sorted(columns):
        x_positions[col_idx] = current_x
        current_x += columns[col_idx] + COLUMN_GAP

    y_positions = {}
    current_y = 130
    for row_idx in sorted(rows):
        y_positions[row_idx] = current_y
        current_y += rows[row_idx] + ROW_GAP

    for name, (col_idx, row_idx) in LAYOUT.items():
        x = x_positions[col_idx]
        y = y_positions[row_idx]
        height = box_height(tables[name])
        positions[name] = (x, y, BOX_WIDTH, height)

    width = current_x - COLUMN_GAP + 80
    height = current_y - ROW_GAP + 80
    return positions, width, height


def relation_color(column_name: str) -> str:
    if column_name in {"khach_hang_id", "nguoi_danh_gia_id"}:
        return "#2a6f97"
    if column_name in {"tho_id", "nguoi_bi_danh_gia_id", "user_id"}:
        return "#8f5fd7"
    return PALETTE["line"]


def simplify_type(type_text: str) -> str:
    lowered = type_text.lower()
    if lowered.startswith("enum("):
        return "enum"
    if lowered.startswith("varchar("):
        return "varchar"
    if lowered.startswith("bigint"):
        return "bigint"
    if lowered.startswith("int"):
        return "int"
    if lowered.startswith("tinyint"):
        return "tinyint"
    if lowered.startswith("decimal("):
        return "decimal"
    if lowered.startswith("timestamp"):
        return "timestamp"
    if lowered.startswith("datetime"):
        return "datetime"
    if lowered.startswith("date"):
        return "date"
    if lowered.startswith("json"):
        return "json"
    if lowered.startswith("text"):
        return "text"
    if lowered.startswith("longtext"):
        return "longtext"
    return type_text.split()[0][:12]


def draw_dashed_line(draw: ImageDraw.ImageDraw, start: tuple[int, int], end: tuple[int, int], color: str) -> None:
    x1, y1 = start
    x2, y2 = end
    dash = 10
    gap = 6

    if x1 == x2:
        direction = 1 if y2 >= y1 else -1
        distance = abs(y2 - y1)
        progress = 0
        while progress < distance:
            seg_end = min(progress + dash, distance)
            draw.line([(x1, y1 + progress * direction), (x2, y1 + seg_end * direction)], fill=color, width=3)
            progress += dash + gap
    elif y1 == y2:
        direction = 1 if x2 >= x1 else -1
        distance = abs(x2 - x1)
        progress = 0
        while progress < distance:
            seg_end = min(progress + dash, distance)
            draw.line([(x1 + progress * direction, y1), (x1 + seg_end * direction, y2)], fill=color, width=3)
            progress += dash + gap


def draw_arrow_head(draw: ImageDraw.ImageDraw, point: tuple[int, int], direction: str, color: str) -> None:
    x, y = point
    size = 8
    if direction == "left":
        polygon = [(x, y), (x + size, y - size // 2), (x + size, y + size // 2)]
    elif direction == "right":
        polygon = [(x, y), (x - size, y - size // 2), (x - size, y + size // 2)]
    elif direction == "up":
        polygon = [(x, y), (x - size // 2, y + size), (x + size // 2, y + size)]
    else:
        polygon = [(x, y), (x - size // 2, y - size), (x + size // 2, y - size)]
    draw.polygon(polygon, fill=color)


def draw_crow_foot(draw: ImageDraw.ImageDraw, point: tuple[int, int], direction: str, color: str) -> None:
    x, y = point
    span = 11
    length = 14
    if direction == "right":
        draw.line([(x, y), (x + length, y - span)], fill=color, width=3)
        draw.line([(x, y), (x + length, y)], fill=color, width=3)
        draw.line([(x, y), (x + length, y + span)], fill=color, width=3)
    elif direction == "left":
        draw.line([(x, y), (x - length, y - span)], fill=color, width=3)
        draw.line([(x, y), (x - length, y)], fill=color, width=3)
        draw.line([(x, y), (x - length, y + span)], fill=color, width=3)
    elif direction == "up":
        draw.line([(x, y), (x - span, y - length)], fill=color, width=3)
        draw.line([(x, y), (x, y - length)], fill=color, width=3)
        draw.line([(x, y), (x + span, y - length)], fill=color, width=3)
    else:
        draw.line([(x, y), (x - span, y + length)], fill=color, width=3)
        draw.line([(x, y), (x, y + length)], fill=color, width=3)
        draw.line([(x, y), (x + span, y + length)], fill=color, width=3)


def route_points(src_rect: tuple[int, int, int, int], dst_rect: tuple[int, int, int, int]) -> tuple[list[tuple[int, int]], str, str]:
    src_side, dst_side = choose_sides(src_rect, dst_rect)
    start = connection_points(src_rect, src_side)
    end = connection_points(dst_rect, dst_side)

    if src_side in {"left", "right"}:
        mid_x = round((start[0] + end[0]) / 2)
        points = [start, (mid_x, start[1]), (mid_x, end[1]), end]
    else:
        mid_y = round((start[1] + end[1]) / 2)
        points = [start, (start[0], mid_y), (end[0], mid_y), end]

    return points, src_side, dst_side


def relation_label(relation: Relation) -> str:
    return "(1,n)"


def label_center(points: list[tuple[int, int]]) -> tuple[int, int]:
    (x1, y1), (x2, y2) = points[1], points[2]
    return ((x1 + x2) // 2, (y1 + y2) // 2)


def draw_relation_label(
    draw: ImageDraw.ImageDraw,
    center: tuple[int, int],
    text: str,
    font: ImageFont.ImageFont,
    border_color: str,
) -> None:
    text_bbox = draw.textbbox((0, 0), text, font=font)
    text_width = text_bbox[2] - text_bbox[0]
    text_height = text_bbox[3] - text_bbox[1]
    padding_x = 10
    padding_y = 5
    x = center[0] - (text_width // 2) - padding_x
    y = center[1] - (text_height // 2) - padding_y
    draw.rounded_rectangle(
        [x, y, x + text_width + padding_x * 2, y + text_height + padding_y * 2],
        radius=10,
        fill="#ffffff",
        outline=border_color,
        width=2,
    )
    draw.text((x + padding_x, y + padding_y - 1), text, font=font, fill=border_color)


def draw_table_box(
    draw: ImageDraw.ImageDraw,
    table: Table,
    rect: tuple[int, int, int, int],
    font_title: ImageFont.ImageFont,
    font_body: ImageFont.ImageFont,
) -> None:
    x, y, width, height = rect
    radius = 12
    draw.rounded_rectangle([x, y, x + width, y + height], radius=radius, fill=PALETTE["box_fill"], outline=PALETTE["box_stroke"], width=2)
    draw.rounded_rectangle([x, y, x + width, y + HEADER_HEIGHT], radius=radius, fill=PALETTE["header_fill"], outline=PALETTE["header_fill"], width=1)
    draw.rectangle([x, y + HEADER_HEIGHT - radius, x + width, y + HEADER_HEIGHT], fill=PALETTE["header_fill"])
    draw.text((x + 14, y + 8), table.name, font=font_title, fill=PALETTE["header_text"])

    columns = prioritize_columns(table)
    row_y = y + HEADER_HEIGHT + 8
    for index, column in enumerate(columns):
        if index > 0:
            draw.line([(x + 10, row_y - 6), (x + width - 10, row_y - 6)], fill="#edf1f5", width=1)
        label_parts = []
        if column.name in table.primary_keys:
            label_parts.append("PK")
        if column.name in table.foreign_keys:
            label_parts.append("FK")
        label = " ".join(label_parts)

        if label == "PK FK":
            color = "#7c5cff"
        elif label == "PK":
            color = PALETTE["pk"]
        elif label == "FK":
            color = PALETTE["fk"]
        else:
            color = PALETTE["muted"]

        prefix = f"{label:<5}" if label else "     "
        text = f"{prefix} {column.name}"
        draw.text((x + 14, row_y), text, font=font_body, fill=color)
        type_text = simplify_type(column.type)
        bbox = draw.textbbox((0, 0), type_text, font=font_body)
        type_width = bbox[2] - bbox[0]
        draw.text((x + width - 14 - type_width, row_y), type_text, font=font_body, fill=PALETTE["muted"])
        row_y += ROW_HEIGHT

    hidden_count = max(0, len(table.columns) - len(columns))
    if hidden_count:
        footer_text = f"+ {hidden_count} cot khac"
        draw.text((x + 14, y + height - 26), footer_text, font=font_body, fill=PALETTE["muted"])


def connection_points(rect: tuple[int, int, int, int], side: str) -> tuple[int, int]:
    x, y, width, height = rect
    if side == "left":
        return (x, y + height // 2)
    if side == "right":
        return (x + width, y + height // 2)
    if side == "top":
        return (x + width // 2, y)
    return (x + width // 2, y + height)


def choose_sides(src: tuple[int, int, int, int], dst: tuple[int, int, int, int]) -> tuple[str, str]:
    sx, sy, sw, sh = src
    dx, dy, dw, dh = dst
    src_center = (sx + sw / 2, sy + sh / 2)
    dst_center = (dx + dw / 2, dy + dh / 2)
    delta_x = dst_center[0] - src_center[0]
    delta_y = dst_center[1] - src_center[1]

    if abs(delta_x) >= abs(delta_y):
        return ("right", "left") if delta_x >= 0 else ("left", "right")
    return ("bottom", "top") if delta_y >= 0 else ("top", "bottom")


def draw_relation(
    draw: ImageDraw.ImageDraw,
    relation: Relation,
    positions: dict[str, tuple[int, int, int, int]],
    label_font: ImageFont.ImageFont,
) -> None:
    src_rect = positions[relation.table]
    dst_rect = positions[relation.ref_table]
    points, src_side, dst_side = route_points(src_rect, dst_rect)
    start = points[0]
    end = points[-1]
    color = relation_color(relation.column)

    for start_point, end_point in zip(points, points[1:]):
        draw_dashed_line(draw, start_point, end_point, color)

    draw_arrow_head(draw, end, dst_side, color)
    draw_crow_foot(draw, start, src_side, color)
    draw_relation_label(draw, label_center(points), relation_label(relation), label_font, color)


def render_png(output_path: Path, tables: dict[str, Table], relations: list[Relation]) -> None:
    positions, width, height = build_layout(tables)
    image = Image.new("RGBA", (width, height), "#ffffff")
    draw = ImageDraw.Draw(image)

    for y in range(height):
        ratio = y / max(height - 1, 1)
        start = tuple(int(PALETTE_RGB["bg_top"][i] * (1 - ratio) + PALETTE_RGB["bg_bottom"][i] * ratio) for i in range(3))
        draw.line([(0, y), (width, y)], fill=start, width=1)

    title_font = default_font(28, bold=True)
    subtitle_font = default_font(16)
    box_title_font = default_font(18, bold=True)
    body_font = default_font(15)
    relation_font = default_font(14, bold=True)

    draw.text((80, 34), "ERD - find_workers", font=title_font, fill=PALETTE["title"])
    draw.text((80, 72), "Chi gom cac bang co quan he khoa ngoai trong file SQL", font=subtitle_font, fill=PALETTE["subtitle"])

    for relation in relations:
        draw_relation(draw, relation, positions, relation_font)

    for name, rect in positions.items():
        draw_table_box(draw, tables[name], rect, box_title_font, body_font)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    image.save(output_path)


def hex_to_rgb(value: str) -> tuple[int, int, int]:
    value = value.lstrip("#")
    return tuple(int(value[i : i + 2], 16) for i in (0, 2, 4))


PALETTE_RGB = {
    "bg_top": hex_to_rgb(PALETTE["bg_top"]),
    "bg_bottom": hex_to_rgb(PALETTE["bg_bottom"]),
}


def svg_text(x: int, y: int, content: str, size: int, color: str, weight: str = "400") -> str:
    escaped = (
        content.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "&quot;")
    )
    return f'<text x="{x}" y="{y}" font-family="Arial, Segoe UI, sans-serif" font-size="{size}" font-weight="{weight}" fill="{color}">{escaped}</text>'


def svg_line(points: list[tuple[int, int]], color: str) -> str:
    point_text = " ".join(f"{x},{y}" for x, y in points)
    return f'<polyline points="{point_text}" fill="none" stroke="{color}" stroke-width="3" stroke-dasharray="10 6" stroke-linecap="round" stroke-linejoin="round" />'


def svg_arrow(point: tuple[int, int], direction: str, color: str) -> str:
    x, y = point
    size = 8
    if direction == "left":
        points = [(x, y), (x + size, y - size // 2), (x + size, y + size // 2)]
    elif direction == "right":
        points = [(x, y), (x - size, y - size // 2), (x - size, y + size // 2)]
    elif direction == "up":
        points = [(x, y), (x - size // 2, y + size), (x + size // 2, y + size)]
    else:
        points = [(x, y), (x - size // 2, y - size), (x + size // 2, y - size)]
    point_text = " ".join(f"{px},{py}" for px, py in points)
    return f'<polygon points="{point_text}" fill="{color}" />'


def svg_crow_foot(point: tuple[int, int], direction: str, color: str) -> str:
    x, y = point
    span = 11
    length = 14
    if direction == "right":
        lines = [[(x, y), (x + length, y - span)], [(x, y), (x + length, y)], [(x, y), (x + length, y + span)]]
    elif direction == "left":
        lines = [[(x, y), (x - length, y - span)], [(x, y), (x - length, y)], [(x, y), (x - length, y + span)]]
    elif direction == "up":
        lines = [[(x, y), (x - span, y - length)], [(x, y), (x, y - length)], [(x, y), (x + span, y - length)]]
    else:
        lines = [[(x, y), (x - span, y + length)], [(x, y), (x, y + length)], [(x, y), (x + span, y + length)]]
    return "".join(svg_line(line, color).replace('stroke-dasharray="10 6" ', "") for line in lines)


def svg_relation_label(center: tuple[int, int], text: str, color: str) -> str:
    x, y = center
    width = 56
    height = 24
    left = x - width // 2
    top = y - height // 2
    return "".join(
        [
            f'<rect x="{left}" y="{top}" rx="10" ry="10" width="{width}" height="{height}" fill="#ffffff" stroke="{color}" stroke-width="2" />',
            svg_text(left + 10, top + 17, text, 14, color, "700"),
        ]
    )


def render_svg(output_path: Path, tables: dict[str, Table], relations: list[Relation]) -> None:
    positions, width, height = build_layout(tables)
    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">',
        "<defs>",
        '<linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">',
        f'<stop offset="0%" stop-color="{PALETTE["bg_top"]}" />',
        f'<stop offset="100%" stop-color="{PALETTE["bg_bottom"]}" />',
        "</linearGradient>",
        "</defs>",
        f'<rect width="{width}" height="{height}" fill="url(#bg)" />',
        svg_text(80, 56, "ERD - find_workers", 28, PALETTE["title"], "700"),
        svg_text(80, 86, "Chi gom cac bang co quan he khoa ngoai trong file SQL", 16, PALETTE["subtitle"]),
    ]

    for relation in relations:
        src_rect = positions[relation.table]
        dst_rect = positions[relation.ref_table]
        points, src_side, dst_side = route_points(src_rect, dst_rect)
        start = points[0]
        end = points[-1]
        color = relation_color(relation.column)

        parts.append(svg_line(points, color))
        parts.append(svg_arrow(end, dst_side, color))
        parts.append(svg_crow_foot(start, src_side, color))
        parts.append(svg_relation_label(label_center(points), relation_label(relation), color))

    for name, rect in positions.items():
        table = tables[name]
        x, y, width_box, height_box = rect
        columns = prioritize_columns(table)
        parts.extend(
            [
                f'<rect x="{x}" y="{y}" rx="12" ry="12" width="{width_box}" height="{height_box}" fill="{PALETTE["box_fill"]}" stroke="{PALETTE["box_stroke"]}" stroke-width="2" />',
                f'<rect x="{x}" y="{y}" rx="12" ry="12" width="{width_box}" height="{HEADER_HEIGHT}" fill="{PALETTE["header_fill"]}" />',
                f'<rect x="{x}" y="{y + HEADER_HEIGHT - 12}" width="{width_box}" height="12" fill="{PALETTE["header_fill"]}" />',
                svg_text(x + 14, y + 23, table.name, 18, PALETTE["header_text"], "700"),
            ]
        )

        row_y = y + HEADER_HEIGHT + 24
        for index, column in enumerate(columns):
            if index > 0:
                parts.append(f'<line x1="{x + 10}" y1="{row_y - 14}" x2="{x + width_box - 10}" y2="{row_y - 14}" stroke="#edf1f5" stroke-width="1" />')

            labels = []
            if column.name in table.primary_keys:
                labels.append("PK")
            if column.name in table.foreign_keys:
                labels.append("FK")
            label = " ".join(labels)

            if label == "PK FK":
                color = "#7c5cff"
            elif label == "PK":
                color = PALETTE["pk"]
            elif label == "FK":
                color = PALETTE["fk"]
            else:
                color = PALETTE["muted"]

            prefix = f"{label:<5}" if label else "     "
            parts.append(svg_text(x + 14, row_y, f"{prefix} {column.name}", 15, color))
            parts.append(svg_text(x + width_box - 86, row_y, simplify_type(column.type), 15, PALETTE["muted"]))
            row_y += ROW_HEIGHT

        hidden_count = max(0, len(table.columns) - len(columns))
        if hidden_count:
            parts.append(svg_text(x + 14, y + height_box - 10, f"+ {hidden_count} cot khac", 15, PALETTE["muted"]))

    parts.append("</svg>")
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text("\n".join(parts), encoding="utf-8")


def main() -> None:
    parser = argparse.ArgumentParser(description="Generate an ERD image from a MySQL dump.")
    parser.add_argument("sql_file", help="Path to the SQL dump")
    parser.add_argument("--png", default="output/find_workers_erd.png", help="PNG output path")
    parser.add_argument("--svg", default="output/find_workers_erd.svg", help="SVG output path")
    args = parser.parse_args()

    sql_path = Path(args.sql_file)
    sql = sql_path.read_text(encoding="utf-8", errors="ignore")
    tables = parse_tables(sql)
    relations = parse_relations(sql)

    related_names = tables_with_relations(relations)
    related_tables = {name: table for name, table in tables.items() if name in related_names}
    filtered_relations = [relation for relation in relations if relation.table in related_tables and relation.ref_table in related_tables]

    missing_layout = [name for name in related_tables if name not in LAYOUT]
    if missing_layout:
        raise SystemExit(f"Missing layout positions for tables: {', '.join(sorted(missing_layout))}")

    render_png(Path(args.png), related_tables, filtered_relations)
    render_svg(Path(args.svg), related_tables, filtered_relations)


if __name__ == "__main__":
    main()
