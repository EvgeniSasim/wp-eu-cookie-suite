#!/usr/bin/env python3
"""Generate WordPress.org plugin directory marketing assets."""

from __future__ import annotations

import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "wordpress-org" / "assets"

EU_BLUE = (0, 51, 153)
EU_BLUE_DARK = (0, 38, 115)
ACCENT = (0, 115, 170)
WHITE = (255, 255, 255)
LIGHT = (245, 247, 250)
MUTED = (100, 116, 139)
GREEN = (22, 163, 74)
CARD = (255, 255, 255)


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
	candidates = (
		"/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
		"/Library/Fonts/Arial Bold.ttf" if bold else "/Library/Fonts/Arial.ttf",
		"/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
	)
	for path in candidates:
		if os.path.exists(path):
			return ImageFont.truetype(path, size)
	return ImageFont.load_default()


def draw_shield(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int, fill: tuple[int, int, int]) -> None:
	w = size
	h = int(size * 1.15)
	top = cy - h // 2
	points = [
		(cx, top),
		(cx + w // 2, top + h // 4),
		(cx + w // 2, top + h // 2),
		(cx, top + h),
		(cx - w // 2, top + h // 2),
		(cx - w // 2, top + h // 4),
	]
	draw.polygon(points, fill=fill)
	draw.rectangle((cx - w // 6, top + h // 3, cx + w // 6, top + h // 2), fill=WHITE)


def make_icon(size: int) -> Image.Image:
	img = Image.new("RGBA", (size, size), EU_BLUE)
	draw = ImageDraw.Draw(img)
	draw_shield(draw, size // 2, size // 2 - size // 16, int(size * 0.55), ACCENT)
	font = load_font(max(12, size // 8), bold=True)
	label = "P"
	bbox = draw.textbbox((0, 0), label, font=font)
	draw.text(
		((size - (bbox[2] - bbox[0])) // 2, (size - (bbox[3] - bbox[1])) // 2 - size // 20),
		label,
		fill=WHITE,
		font=font,
	)
	return img


def make_banner(width: int, height: int) -> Image.Image:
	img = Image.new("RGB", (width, height), EU_BLUE)
	draw = ImageDraw.Draw(img)
	for x in range(width):
		shade = int(EU_BLUE[0] + (EU_BLUE_DARK[0] - EU_BLUE[0]) * x / width)
		draw.line([(x, 0), (x, height)], fill=(shade, EU_BLUE[1], EU_BLUE[2]))

	draw_shield(draw, width // 5, height // 2, min(height, 120), ACCENT)
	title = load_font(max(28, height // 7), bold=True)
	sub = load_font(max(16, height // 12))
	draw.text((width // 3, height // 2 - height // 5), "Privaro Cookie Consent Banner", fill=WHITE, font=title)
	draw.text((width // 3, height // 2 + height // 12), "GDPR-ready cookie consent for WordPress", fill=(220, 230, 245), font=sub)
	return img


def rounded_rect(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], radius: int, fill: tuple[int, int, int]) -> None:
	x0, y0, x1, y1 = box
	draw.rounded_rectangle(box, radius=radius, fill=fill)


def make_screenshot(index: int, title: str, subtitle: str) -> Image.Image:
	width, height = 1280, 720
	img = Image.new("RGB", (width, height), LIGHT)
	draw = ImageDraw.Draw(img)

	# WP admin chrome mock
	draw.rectangle((0, 0, width, 46), fill=(35, 40, 45))
	draw.rectangle((0, 46, 180, height), fill=(44, 51, 56))
	font = load_font(18, bold=True)
	draw.text((24, 12), "Privaro Cookie Consent Banner", fill=WHITE, font=font)
	draw.text((24, 80), "Dashboard", fill=(200, 205, 210), font=load_font(14))
	draw.text((24, 110), "Banner", fill=(200, 205, 210), font=load_font(14))
	draw.text((24, 140), "Cookies", fill=(200, 205, 210), font=load_font(14))
	draw.text((24, 170), "Scanner", fill=(200, 205, 210), font=load_font(14))
	draw.text((24, 200), "Consent Log", fill=(200, 205, 210), font=load_font(14))
	draw.text((24, 230), "Integrations", fill=(200, 205, 210), font=load_font(14))

	title_font = load_font(34, bold=True)
	sub_font = load_font(20)
	draw.text((220, 70), title, fill=(30, 41, 59), font=title_font)
	draw.text((220, 120), subtitle, fill=MUTED, font=sub_font)

	if index == 1:
		rounded_rect(draw, (220, 180, 1180, 620), 12, CARD)
		draw.text((250, 210), "We use cookies", fill=(30, 41, 59), font=load_font(28, bold=True))
		draw.text((250, 260), "Accept statistics and marketing cookies, or manage preferences.", fill=MUTED, font=load_font(18))
		rounded_rect(draw, (250, 520, 390, 570), 8, EU_BLUE)
		draw.text((285, 535), "Accept all", fill=WHITE, font=load_font(16, bold=True))
		rounded_rect(draw, (410, 520, 560, 570), 8, (226, 232, 240))
		draw.text((430, 535), "Reject all", fill=(30, 41, 59), font=load_font(16, bold=True))
	elif index == 2:
		headers = ["Name", "Category", "Service", "Duration"]
		xs = [240, 420, 620, 820]
		for i, h in enumerate(headers):
			draw.text((xs[i], 190), h, fill=MUTED, font=load_font(14, bold=True))
		rows = [
			("_ga", "Statistics", "Google Analytics", "2 years"),
			("_fbp", "Marketing", "Facebook", "3 months"),
			("PHPSESSID", "Necessary", "WordPress", "Session"),
		]
		y = 230
		for row in rows:
			for i, cell in enumerate(row):
				draw.text((xs[i], y), cell, fill=(30, 41, 59), font=load_font(16))
			y += 42
	elif index == 3:
		headers = ["Date", "Event", "Categories", "UUID"]
		xs = [240, 420, 620, 860]
		for i, h in enumerate(headers):
			draw.text((xs[i], 190), h, fill=MUTED, font=load_font(14, bold=True))
		rows = [
			("2026-06-21", "accept_all", "statistics, marketing", "a1b2…f4"),
			("2026-06-21", "save_preferences", "statistics", "c3d4…e5"),
		]
		y = 230
		for row in rows:
			for i, cell in enumerate(row):
				draw.text((xs[i], y), cell, fill=(30, 41, 59), font=load_font(16))
			y += 42
	else:
		draw.text((240, 190), "Google Consent Mode v2", fill=(30, 41, 59), font=load_font(22, bold=True))
		draw.text((240, 240), "Script blocker enabled", fill=GREEN, font=load_font(18, bold=True))
		draw.text((240, 290), "Google Analytics cookie guard", fill=GREEN, font=load_font(18, bold=True))
		draw.text((240, 340), "Google Site Kit integration", fill=GREEN, font=load_font(18, bold=True))
		draw.text((240, 390), "Iframe placeholders (YouTube, Vimeo, Maps)", fill=GREEN, font=load_font(18, bold=True))

	watermark = load_font(14)
	draw.text((220, height - 36), "Mock screenshot for WordPress.org listing — replace with live captures when available.", fill=(148, 163, 184), font=watermark)
	return img


def main() -> None:
	OUT.mkdir(parents=True, exist_ok=True)
	assets = {
		"icon-128x128.png": make_icon(128),
		"icon-256x256.png": make_icon(256),
		"banner-772x250.png": make_banner(772, 250),
		"banner-1544x500.png": make_banner(1544, 500),
	}
	screens = [
		(1, "Cookie consent banner", "Customizable banner with categories and reject-all support."),
		(2, "Cookie inventory", "Scanner results and manual cookie management."),
		(3, "Consent log", "Local audit trail for compliance accountability."),
		(4, "Integrations", "Google Consent Mode v2, script blocking, and iframe placeholders."),
	]
	for idx, title, subtitle in screens:
		assets[f"screenshot-{idx}.png"] = make_screenshot(idx, title, subtitle)

	for name, image in assets.items():
		path = OUT / name
		if name.endswith(".png") and image.mode == "RGBA" and "icon" in name:
			image.save(path, "PNG")
		else:
			image.convert("RGB").save(path, "PNG")
		print(f"Wrote {path}")


if __name__ == "__main__":
	main()
