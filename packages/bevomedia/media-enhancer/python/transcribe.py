import argparse
import os
import sys
import requests

def transcribe(input_path, output_path, format_type, model):
    api_key = os.environ.get("OPENAI_API_KEY")
    if not api_key:
        print("Error: OPENAI_API_KEY environment variable not set", file=sys.stderr)
        sys.exit(1)

    url = "https://api.openai.com/v1/audio/transcriptions"
    
    headers = {
        "Authorization": f"Bearer {api_key}"
    }

    try:
        with open(input_path, "rb") as audio_file:
            files = {
                "file": audio_file,
            }
            data = {
                "model": model,
                "response_format": format_type
            }

            response = requests.post(url, headers=headers, files=files, data=data)

            if response.status_code != 200:
                print(f"OpenAI API Error: {response.text}", file=sys.stderr)
                sys.exit(1)

            with open(output_path, "w", encoding="utf-8") as out:
                out.write(response.text)
                
    except Exception as e:
        print(f"Transcription failed: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Transcribe audio using OpenAI Whisper API")
    parser.add_argument("--input", required=True, help="Path to input audio file")
    parser.add_argument("--output", required=True, help="Path to save output subtitle file")
    parser.add_argument("--format", default="srt", choices=["srt", "vtt"], help="Subtitle format (srt/vtt)")
    parser.add_argument("--model", default="whisper-1", help="Whisper model to use")

    args = parser.parse_args()

    transcribe(args.input, args.output, args.format, args.model)
