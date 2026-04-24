import argparse
import sys
import os
import math
from PIL import Image, ImageDraw, ImageFont
import numpy as np
from moviepy import ImageClip, ColorClip, CompositeVideoClip, VideoClip
from moviepy.video.fx import FadeIn, FadeOut


def get_font(font_size):
    """Try to load a system font, fall back to PIL default."""
    font_paths = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
        "/usr/share/fonts/truetype/freefont/FreeSans.ttf",
    ]
    for path in font_paths:
        if os.path.exists(path):
            return ImageFont.truetype(path, font_size)
    return ImageFont.load_default()


def word_wrap(text, font, max_width):
    """Wrap text into lines that fit within max_width pixels."""
    tmp = Image.new("RGB", (max_width * 2, 100))
    draw = ImageDraw.Draw(tmp)
    lines, current = [], []
    for word in text.split():
        test = ' '.join(current + [word])
        bbox = draw.textbbox((0, 0), test, font=font)
        if bbox[2] - bbox[0] <= max_width:
            current.append(word)
        else:
            if current:
                lines.append(' '.join(current))
            current = [word]
    if current:
        lines.append(' '.join(current))
    return lines


def render_text_to_canvas(text, width, height, font_size=52, bg_color=(30, 144, 255), text_color=(255, 255, 255)):
    """
    Render text to a Pillow canvas. If text is taller than height,
    the canvas will be taller (for scrolling). Returns (numpy_array, canvas_height).
    """
    padding_x = int(width * 0.08)
    padding_y = int(height * 0.06)
    max_text_width = width - 2 * padding_x

    font = get_font(font_size)
    lines = word_wrap(text, font, max_text_width)

    # Measure line height
    tmp = Image.new("RGB", (width, 100))
    draw_tmp = ImageDraw.Draw(tmp)
    bbox = draw_tmp.textbbox((0, 0), "Ay", font=font)
    line_h = (bbox[3] - bbox[1]) + int(font_size * 0.35)

    total_text_h = len(lines) * line_h + 2 * padding_y
    canvas_h = max(height, total_text_h)

    canvas = Image.new("RGB", (width, canvas_h), color=bg_color)
    draw = ImageDraw.Draw(canvas)

    # Vertically center text if it fits, otherwise start from top
    if total_text_h <= height:
        y = (height - total_text_h) // 2 + padding_y
    else:
        y = padding_y

    for line in lines:
        draw.text((padding_x, y), line, font=font, fill=text_color)
        y += line_h

    return np.array(canvas), canvas_h


def apply_effect_to_clip(clip, style, duration, video_height):
    """Apply animation effect to an ImageClip."""
    if style == 'fade':
        fade_dur = min(1.0, duration / 4)
        return clip.with_effects([FadeIn(fade_dur), FadeOut(fade_dur)])

    elif style == 'slide':
        clip_h = clip.h
        def slide_pos(t):
            progress = min(1.0, t / 1.0)
            ease = progress * progress * (3 - 2 * progress)
            start_y = video_height
            end_y = max(0, (video_height - clip_h) // 2)
            return ('center', int(start_y + (end_y - start_y) * ease))
        return clip.with_position(slide_pos)

    elif style == 'bounce':
        clip_h = clip.h
        def bounce_pos(t):
            center_y = max(0, (video_height - clip_h) // 2)
            offset = int(25 * abs(math.sin(t * 3)))
            return ('center', center_y + offset)
        return clip.with_position(bounce_pos)

    return clip


def make_scroll_frame(canvas_arr, canvas_h, frame_h, duration):
    """Return a frame-maker function for a scrolling video clip."""
    scroll_dist = canvas_h - frame_h

    def make_frame(t):
        progress = min(1.0, t / duration)
        ease = progress * progress * (3 - 2 * progress)
        y = int(ease * scroll_dist)
        return canvas_arr[y:y + frame_h, :, :]

    return make_frame


def create_animation(text, output, style, resolution, fps):
    width, height = map(int, resolution.split('x'))

    words = text.split()
    duration = max(5.0, len(words) / 2.5)

    canvas_arr, canvas_h = render_text_to_canvas(text, width, height)
    needs_scroll = canvas_h > height

    bg = ColorClip(size=(width, height), color=(30, 144, 255)).with_duration(duration)

    # ── TYPEWRITER ─────────────────────────────────────────────────────────────
    if style == 'typewriter':
        full = text
        n = len(full)
        typing_dur = duration * 0.75
        char_dur = typing_dur / max(n, 1)

        clips = [bg]
        for i in range(1, n + 1):
            partial = full[:i]
            arr, _ = render_text_to_canvas(partial, width, height)
            # Crop to frame height
            arr = arr[:height, :, :]
            c = (ImageClip(arr)
                 .with_duration(char_dur if i < n else (duration - (i - 1) * char_dur))
                 .with_start((i - 1) * char_dur))
            clips.append(c)

        video = CompositeVideoClip(clips)

    # ── LONG TEXT → SCROLL + EFFECT ────────────────────────────────────────────
    elif needs_scroll:
        make_frame = make_scroll_frame(canvas_arr, canvas_h, height, duration)
        scroll_clip = VideoClip(make_frame, duration=duration)

        if style == 'fade':
            fade_dur = min(1.0, duration / 5)
            scroll_clip = scroll_clip.with_effects([FadeIn(fade_dur), FadeOut(fade_dur)])
        # For slide/bounce with scroll, fade is the most natural complement
        elif style in ('slide', 'bounce'):
            fade_dur = min(0.5, duration / 6)
            scroll_clip = scroll_clip.with_effects([FadeIn(fade_dur), FadeOut(fade_dur)])

        video = scroll_clip

    # ── SHORT TEXT → STATIC FRAME + EFFECT ────────────────────────────────────
    else:
        txt_arr = canvas_arr[:height, :, :]
        txt_clip = ImageClip(txt_arr).with_duration(duration)
        txt_clip = apply_effect_to_clip(txt_clip, style, duration, height)
        video = CompositeVideoClip([bg, txt_clip])

    video.write_videofile(output, fps=fps, codec='libx264', audio=False, logger=None)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Animate text using Pillow + MoviePy")
    parser.add_argument("--text", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--style", default="fade")
    parser.add_argument("--resolution", default="1920x1080")
    parser.add_argument("--fps", type=int, default=30)

    args = parser.parse_args()

    try:
        create_animation(args.text, args.output, args.style, args.resolution, args.fps)
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)
