#!/usr/bin/env python3
"""
하루샵(Haru Shop) 아이콘·로고 생성 스크립트
- 미니멀 스타일: 하루(H) 모노그램 + 따뜻한 크림/브라운 톤
"""

import os
import sys

try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    print("Pillow가 필요합니다. 설치: pip install Pillow")
    sys.exit(1)

OUTPUT_DIR = r"C:\Users\haru\Documents\project\github\HaruShop\resources"

# 따뜻하고 차분한 톤
COLORS = {
    "cream": "#FAF7F2",      # 배경
    "brown": "#3D2914",      # 심볼·텍스트 (따뜻한 다크 브라운)
    "brown_soft": "#5C4033", # 로고 서브
    "white": "#FFFFFF",
}


def hex_to_rgb(hex_color: str) -> tuple:
    h = hex_color.lstrip("#")
    return tuple(int(h[i : i + 2], 16) for i in (0, 2, 4))


def get_korean_font(size: int):
    candidates = [
        r"C:\Windows\Fonts\malgun.ttf",
        r"C:\Windows\Fonts\NanumGothic.ttf",
        r"C:\Windows\Fonts\gulim.ttc",
    ]
    for path in candidates:
        if os.path.isfile(path):
            try:
                return ImageFont.truetype(path, size)
            except OSError:
                continue
    return ImageFont.load_default()


def draw_H(draw, cx, cy, r, color_hex, thickness_ratio=0.22):
    """Haru 이니셜 H: 두 개 세로바 + 한 개 가로바, 둥근 모서리"""
    color = hex_to_rgb(color_hex)
    t = r * thickness_ratio
    rad = max(2, int(r * 0.08))

    # 왼쪽 세로바
    x1, y1 = cx - r * 0.72, cy - r * 0.72
    x2, y2 = cx - r * 0.28, cy + r * 0.72
    draw.rounded_rectangle([x1, y1, x2, y2], radius=rad, fill=color)

    # 오른쪽 세로바
    x1, y1 = cx + r * 0.28, cy - r * 0.72
    x2, y2 = cx + r * 0.72, cy + r * 0.72
    draw.rounded_rectangle([x1, y1, x2, y2], radius=rad, fill=color)

    # 가로바 (중앙)
    x1, y1 = cx - r * 0.72, cy - t
    x2, y2 = cx + r * 0.72, cy + t
    draw.rounded_rectangle([x1, y1, x2, y2], radius=rad, fill=color)


def create_icon(size: int) -> Image.Image:
    """크림색 둥근 사각 + H 모노그램"""
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.ImageDraw(img)

    margin = size * 0.08
    box = [margin, margin, size - margin, size - margin]
    corner = int(size * 0.24)
    draw.rounded_rectangle(box, radius=corner, fill=hex_to_rgb(COLORS["cream"]))

    cx = size // 2
    cy = size // 2
    symbol_r = size * 0.32
    draw_H(draw, cx, cy, symbol_r, COLORS["brown"])

    return img


def create_logo(icon_size: int = 100, width: int = 420, height: int = 120) -> Image.Image:
    """로고: 아이콘 + '하루샵' 텍스트, 여백 충분히"""
    img = Image.new("RGBA", (width, height), (255, 255, 255, 0))
    draw = ImageDraw.ImageDraw(img)

    icon = create_icon(icon_size)
    icon_y = (height - icon_size) // 2
    img.paste(icon, (24, icon_y), icon)

    text = "하루샵"
    font_size = int(height * 0.42)
    font = get_korean_font(font_size)
    text_x = 28 + icon_size
    text_y = (height - font_size) // 2 - 2
    draw.text((text_x, text_y), text, font=font, fill=hex_to_rgb(COLORS["brown"]))

    return img


def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    for size in [32, 64, 128, 256, 512]:
        path = os.path.join(OUTPUT_DIR, f"harushop-icon-{size}.png")
        create_icon(size).save(path, "PNG")
        print(f"저장: {path}")

    logo_path = os.path.join(OUTPUT_DIR, "harushop-logo.png")
    create_logo(icon_size=100, width=420, height=120).save(logo_path, "PNG")
    print(f"저장: {logo_path}")

    favicon_path = os.path.join(OUTPUT_DIR, "harushop-favicon.png")
    create_icon(32).save(favicon_path, "PNG")
    print(f"저장: {favicon_path}")

    print("\n완료: resources 폴더에 아이콘·로고가 생성되었습니다.")


if __name__ == "__main__":
    main()
