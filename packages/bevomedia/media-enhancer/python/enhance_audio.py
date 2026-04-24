import argparse
from df.enhance import enhance, init_df
from df.io import load_audio, save_audio

parser = argparse.ArgumentParser()
parser.add_argument("--input", required=True)
parser.add_argument("--output", required=True)

args = parser.parse_args()

model, df_state, _ = init_df()

audio, _ = load_audio(args.input, sr=df_state.sr())
enhanced = enhance(model, df_state, audio)

save_audio(args.output, enhanced, df_state.sr())