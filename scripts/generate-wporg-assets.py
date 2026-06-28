#!/usr/bin/env python3
"""Generate WordPress.org and GitHub marketing assets for Privaro Cookie Consent Banner."""

from __future__ import annotations

import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "wordpress-org" / "assets"
DOCS = ROOT / "docs" / "assets"

# Brand palette
EU_BLUE = (0, 51, 153)
EU_BLUE_MID = (0, 82, 204)
EU_BLUE_DARK = (0, 32, 96)
ACCENT = (0, 115, 230)
ACCENT_LIGHT = (96, 165, 250)
WHITE = (255, 255, 255)
OFF_WHITE = (248, 250, 252)
SLATE_900 = (15, 23, 42)
SLATE_700 = (51, 65, 85)
SLATE_500 = (100, 116, 139)
SLATE_200 = (226, 232, 240)
GREEN = (22, 163, 74)
GREEN_BG = (220, 252, 231)
ADMIN_SIDEBAR = (30, 41, 59)
ADMIN_TOP = (15, 23, 42)


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


def lerp(a: int, b: int, t: float) -> int:
	return int(a + (b - a) * t)


def vertical_gradient(size: tuple[int, int], top: tuple[int, int, int], bottom: tuple[int, int, int]) -> Image.Image:
	img = Image.new("RGB", size, top)
	draw = ImageDraw.Draw(img)
	w, h = size
	for y in range(h):
		t = y / max(h - 1, 1)
		color = (lerp(top[0], bottom[0], t), lerp(top[1], bottom[1], t), lerp(top[2], bottom[2], t))
		draw.line([(0, y), (w, y)], fill=color)
	return img


def draw_star_field(draw: ImageDraw.ImageDraw, width: int, height: int, count: int = 18) -> None:
	# Subtle EU-inspired star accents (decorative dots, not official flag).
	import random

	rng = random.Random(42)
	for _ in range(count):
		x = rng.randint(0, width)
		y = rng.randint(0, height)
		r = rng.randint(1, 2)
		alpha = rng.randint(40, 90)
		draw.ellipse((x - r, y - r, x + r, y + r), fill=(255, 255, 255, alpha))


def draw_shield(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int, fill: tuple[int, int, int]) -> None:
	w = size
	h = int(size * 1.12)
	top = cy - h // 2
	points = [
		(cx, top),
		(cx + w // 2, top + h // 5),
		(cx + w // 2, top + h // 2),
		(cx, top + h),
		(cx - w // 2, top + h // 2),
		(cx - w // 2, top + h // 5),
	]
	draw.polygon(points, fill=fill)


def draw_cookie(draw: ImageDraw.ImageDraw, cx: int, cy: int, radius: int, base: tuple[int, int, int], chips: tuple[int, int, int]) -> None:
	draw.ellipse((cx - radius, cy - radius, cx + radius, cy + radius), fill=base)
	# Bite.
	draw.pieslice((cx + radius // 2, cy - radius, cx + radius * 2, cy + radius), 200, 320, fill=WHITE)
	for dx, dy in ((-radius // 2, -radius // 4), (0, radius // 3), (radius // 3, -radius // 5)):
		chip_r = max(3, radius // 7)
		draw.ellipse((cx + dx - chip_r, cy + dy - chip_r, cx + dx + chip_r, cy + dy + chip_r), fill=chips)


def draw_check(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int, color: tuple[int, int, int], width: int = 4) -> None:
	points = [
		(cx - size // 3, cy),
		(cx - size // 10, cy + size // 3),
		(cx + size // 2, cy - size // 3),
	]
	draw.line(points[:2], fill=color, width=width, joint="curve")
	draw.line(points[1:], fill=color, width=width, joint="curve")


def make_icon(size: int) -> Image.Image:
	img = vertical_gradient((size, size), EU_BLUE_MID, EU_BLUE_DARK).convert("RGBA")
	draw = ImageDraw.Draw(img)
	margin = size // 10
	draw.rounded_rectangle((margin, margin, size - margin, size - margin), radius=size // 5, fill=(255, 255, 255, 28))

	shield_size = int(size * 0.52)
	draw_shield(draw, size // 2, size // 2 - size // 24, shield_size, WHITE)
	cookie_r = max(6, shield_size // 5)
	draw_cookie(draw, size // 2, size // 2 - size // 24, cookie_r, (234, 179, 98), (180, 120, 50))
	draw_check(draw, size // 2 + shield_size // 4, size // 2 + shield_size // 6, shield_size // 3, GREEN, width=max(2, size // 32))
	return img


def draw_feature_pill(draw: ImageDraw.ImageDraw, x: int, y: int, text: str, font: ImageFont.ImageFont) -> int:
	padding_x, padding_y = 14, 8
	bbox = draw.textbbox((0, 0), text, font=font)
	w = bbox[2] - bbox[0] + padding_x * 2
	h = bbox[3] - bbox[1] + padding_y * 2
	draw.rounded_rectangle((x, y, x + w, y + h), radius=h // 2, fill=(255, 255, 255, 38), outline=(255, 255, 255, 80))
	draw.text((x + padding_x, y + padding_y - 1), text, fill=WHITE, font=font)
	return w + 12


def make_banner(width: int, height: int) -> Image.Image:
	img = vertical_gradient((width, height), EU_BLUE, EU_BLUE_DARK)
	draw = ImageDraw.Draw(img, "RGBA")
	draw_star_field(draw, width, height)

	icon_size = min(height - 40, 140)
	icon = make_icon(icon_size)
	img.paste(icon, (48, (height - icon_size) // 2), icon)

	title_font = load_font(max(26, height // 8), bold=True)
	sub_font = load_font(max(15, height // 14))
	pill_font = load_font(max(12, height // 20), bold=True)

	left = 48 + icon_size + 36
	draw.text((left, height // 2 - height // 4), "Privaro Cookie Consent Banner", fill=WHITE, font=title_font)
	draw.text(
		(left, height // 2 - height // 12),
		"GDPR · ePrivacy · Google Consent Mode v2 · Script blocking",
		fill=(210, 225, 245),
		font=sub_font,
	)

	pill_y = height - max(44, height // 6)
	pill_x = left
	for label in ("Opt-in by default", "Cookie scanner", "WP Consent API", "Multisite"):
		pill_x += draw_feature_pill(draw, pill_x, pill_y, label, pill_font)

	return img.convert("RGB")


def make_social_preview() -> Image.Image:
	"""GitHub / Open Graph card (1280×640)."""
	width, height = 1280, 640
	img = vertical_gradient((width, height), EU_BLUE, EU_BLUE_DARK)
	draw = ImageDraw.Draw(img, "RGBA")
	draw_star_field(draw, width, height, count=24)

	icon = make_icon(200)
	img.paste(icon, (80, (height - 200) // 2), icon)

	title_font = load_font(52, bold=True)
	sub_font = load_font(26)
	body_font = load_font(20)

	draw.text((320, 180), "Privaro Cookie Consent Banner", fill=WHITE, font=title_font)
	draw.text((320, 260), "Professional GDPR cookie consent for WordPress", fill=(220, 232, 248), font=sub_font)
	draw.text(
		(320, 330),
		"Banner · Script blocker · Scanner · Consent log · Google Consent Mode v2",
		fill=(180, 200, 230),
		font=body_font,
	)

	# Mock browser frame with mini banner.
	frame_x, frame_y, frame_w, frame_h = 320, 400, 880, 180
	draw.rounded_rectangle((frame_x, frame_y, frame_x + frame_w, frame_y + frame_h), radius=14, fill=(255, 255, 255, 22))
	draw.rounded_rectangle((frame_x + 16, frame_y + frame_h - 72, frame_x + frame_w - 16, frame_y + frame_h - 16), radius=10, fill=WHITE)
	draw.text((frame_x + 32, frame_y + frame_h - 58), "We value your privacy", fill=SLATE_900, font=load_font(18, bold=True))
	draw.rounded_rectangle((frame_x + frame_w - 280, frame_y + frame_h - 52, frame_x + frame_w - 180, frame_y + frame_h - 24), radius=6, fill=EU_BLUE)
	draw.text((frame_x + frame_w - 258, frame_y + frame_h - 48), "Accept all", fill=WHITE, font=load_font(14, bold=True))
	draw.rounded_rectangle((frame_x + frame_w - 168, frame_y + frame_h - 52, frame_x + frame_w - 48, frame_y + frame_h - 24), radius=6, fill=SLATE_200)
	draw.text((frame_x + frame_w - 148, frame_y + frame_h - 48), "Reject all", fill=SLATE_700, font=load_font(14, bold=True))

	return img.convert("RGB")


def draw_admin_chrome(draw: ImageDraw.ImageDraw, width: int, height: int, active_tab: str) -> None:
	draw.rectangle((0, 0, width, 52), fill=ADMIN_TOP)
	draw.rectangle((0, 52, 220, height), fill=ADMIN_SIDEBAR)
	font = load_font(16, bold=True)
	draw.text((24, 16), "Privaro Cookie Consent Banner", fill=WHITE, font=font)

	tabs = [
		("Dashboard", "dashboard"),
		("Banner", "banner"),
		("Cookies", "cookies"),
		("Scanner", "scanner"),
		("Consent Log", "consent_log"),
		("Integrations", "integrations"),
		("Tools", "tools"),
	]
	y = 72
	tab_font = load_font(14)
	for label, slug in tabs:
		color = ACCENT_LIGHT if slug == active_tab else (203, 213, 225)
		if slug == active_tab:
			draw.rounded_rectangle((12, y - 4, 208, y + 24), radius=6, fill=(51, 65, 85))
		draw.text((24, y), label, fill=color, font=tab_font)
		y += 34


def make_screenshot(index: int, title: str, subtitle: str) -> Image.Image:
	width, height = 1280, 720
	img = Image.new("RGB", (width, height), OFF_WHITE)
	draw = ImageDraw.Draw(img)
	draw_admin_chrome(draw, width, height, ["banner", "cookies", "consent_log", "integrations"][index - 1])

	title_font = load_font(32, bold=True)
	sub_font = load_font(18)
	draw.text((248, 72), title, fill=SLATE_900, font=title_font)
	draw.text((248, 118), subtitle, fill=SLATE_500, font=sub_font)

	content = (248, 160, 1240, 680)

	if index == 1:
		# Live preview panel + floating banner mock.
		draw.rounded_rectangle(content, radius=16, fill=WHITE, outline=SLATE_200, width=2)
		draw.text((272, 188), "Banner preview", fill=SLATE_700, font=load_font(16, bold=True))
		# Website mock inside preview.
		site = (272, 220, 1216, 640)
		draw.rounded_rectangle(site, radius=12, fill=(241, 245, 249))
		draw.rectangle((272, 220, 1216, 258), fill=WHITE)
		draw.text((288, 232), "example.com", fill=SLATE_500, font=load_font(13))
		# Banner bar.
		bx1, by1, bx2, by2 = 288, 560, 1200, 624
		draw.rounded_rectangle((bx1, by1, bx2, by2), radius=10, fill=WHITE, outline=SLATE_200, width=1)
		draw.text((bx1 + 20, by1 + 14), "We use cookies to improve your experience.", fill=SLATE_900, font=load_font(16, bold=True))
		draw.text((bx1 + 20, by1 + 38), "Statistics and marketing cookies help us understand traffic.", fill=SLATE_500, font=load_font(13))
		draw.rounded_rectangle((bx2 - 250, by1 + 36, bx2 - 140, by1 + 68), radius=6, fill=EU_BLUE)
		draw.text((bx2 - 232, by1 + 46), "Accept all", fill=WHITE, font=load_font(13, bold=True))
		draw.rounded_rectangle((bx2 - 128, by1 + 36, bx2 - 20, by1 + 68), radius=6, fill=SLATE_200)
		draw.text((bx2 - 112, by1 + 46), "Reject all", fill=SLATE_700, font=load_font(13, bold=True))
		# Settings sidebar mock.
		draw.rounded_rectangle((272, 280, 520, 540), radius=10, fill=WHITE, outline=SLATE_200)
		draw.text((292, 300), "Primary color", fill=SLATE_700, font=load_font(14, bold=True))
		draw.rounded_rectangle((292, 330, 500, 360), radius=6, fill=EU_BLUE)
		draw.text((292, 378), "Layout: bar bottom", fill=SLATE_500, font=load_font(13))
		draw.text((292, 404), "Theme: light", fill=SLATE_500, font=load_font(13))
	elif index == 2:
		draw.rounded_rectangle(content, radius=16, fill=WHITE, outline=SLATE_200, width=2)
		headers = ["Cookie", "Category", "Service", "Duration", "Status"]
		xs = [272, 440, 620, 820, 1020]
		for i, h in enumerate(headers):
			draw.text((xs[i], 188), h, fill=SLATE_500, font=load_font(13, bold=True))
		draw.line([(272, 212), (1216, 212)], fill=SLATE_200, width=1)
		rows = [
			("_ga", "Statistics", "Google Analytics", "2 years", "Blocked"),
			("_fbp", "Marketing", "Meta Pixel", "3 months", "Blocked"),
			("PHPSESSID", "Necessary", "WordPress", "Session", "Allowed"),
			("wpeu_consent", "Necessary", "Privaro", "1 year", "Allowed"),
		]
		y = 228
		for row in rows:
			for i, cell in enumerate(row):
				color = GREEN if cell == "Allowed" else SLATE_700
				if i == 4 and cell == "Blocked":
					color = (220, 38, 38)
				draw.text((xs[i], y), cell, fill=color if i == 4 else SLATE_900, font=load_font(15))
			y += 40
		draw.rounded_rectangle((272, 580, 420, 620), radius=8, fill=EU_BLUE)
		draw.text((296, 592), "Run scanner", fill=WHITE, font=load_font(14, bold=True))
	elif index == 3:
		draw.rounded_rectangle(content, radius=16, fill=WHITE, outline=SLATE_200, width=2)
		headers = ["Date", "Event", "Categories", "Proof hash"]
		xs = [272, 440, 620, 900]
		for i, h in enumerate(headers):
			draw.text((xs[i], 188), h, fill=SLATE_500, font=load_font(13, bold=True))
		draw.line([(272, 212), (1216, 212)], fill=SLATE_200, width=1)
		rows = [
			("2026-06-21 14:02", "accept_all", "statistics, marketing", "8f3a…c21b"),
			("2026-06-21 13:58", "save_preferences", "statistics", "2b91…7e04"),
			("2026-06-21 13:55", "reject_all", "necessary", "c104…9aa2"),
		]
		y = 228
		for row in rows:
			for i, cell in enumerate(row):
				draw.text((xs[i], y), cell, fill=SLATE_900, font=load_font(15))
			y += 44
		draw.text((272, 580), "Stored locally in your WordPress database — no external service.", fill=SLATE_500, font=load_font(14))
	else:
		draw.rounded_rectangle(content, radius=16, fill=WHITE, outline=SLATE_200, width=2)
		items = [
			("Google Consent Mode v2", "Default denied until consent · updates on banner choice", True),
			("Script blocker", "Blocks GA, GTM, Meta Pixel, Hotjar, Clarity before consent", True),
			("Google Site Kit", "Respects consent for Analytics tag output", True),
			("Iframe placeholders", "YouTube, Vimeo, Google Maps until marketing consent", True),
			("Contact Form 7 reCAPTCHA", "Deferred until marketing consent", True),
		]
		y = 188
		for name, desc, on in items:
			draw.rounded_rectangle((272, y, 1216, y + 72), radius=10, fill=OFF_WHITE, outline=SLATE_200)
			draw.ellipse((292, y + 24, 324, y + 56), fill=GREEN if on else SLATE_200)
			if on:
				draw_check(draw, 308, y + 40, 20, WHITE, width=3)
			draw.text((344, y + 16), name, fill=SLATE_900, font=load_font(17, bold=True))
			draw.text((344, y + 42), desc, fill=SLATE_500, font=load_font(14))
			y += 84

	return img


def sync_docs_assets(assets: dict[str, Image.Image]) -> None:
	DOCS.mkdir(parents=True, exist_ok=True)
	mapping = {
		"icon-256x256.png": "icon-256x256.png",
		"banner-1544x500.png": "hero-banner.png",
		"social-preview.png": "social-preview.png",
	}
	for src_name, dest_name in mapping.items():
		if src_name in assets:
			path = DOCS / dest_name
			img = assets[src_name]
			if img.mode == "RGBA":
				img.save(path, "PNG")
			else:
				img.convert("RGB").save(path, "PNG")
	for i in range(1, 5):
		key = f"screenshot-{i}.png"
		if key in assets:
			assets[key].save(DOCS / key, "PNG")


def main() -> None:
	OUT.mkdir(parents=True, exist_ok=True)
	assets: dict[str, Image.Image] = {
		"icon-128x128.png": make_icon(128),
		"icon-256x256.png": make_icon(256),
		"banner-772x250.png": make_banner(772, 250),
		"banner-1544x500.png": make_banner(1544, 500),
		"social-preview.png": make_social_preview(),
	}
	screens = [
		(1, "Cookie consent banner", "Live preview, primary color, layout, and reject-all support."),
		(2, "Cookie inventory", "Scanner results, categories, and blocking status at a glance."),
		(3, "Consent log", "Local audit trail with proof snapshots for accountability."),
		(4, "Integrations", "Google Consent Mode v2, script blocker, Site Kit, and iframe placeholders."),
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

	sync_docs_assets(assets)
	print(f"Synced docs assets to {DOCS}")


if __name__ == "__main__":
	main()
