import joblib

model = joblib.load("model_final.joblib")
fitur = joblib.load("fitur_model.pkl")

print("=" * 50)
print("Jumlah fitur model :", model.n_features_in_)
print("Jumlah fitur file  :", len(fitur))

print("\nDaftar fitur:")

for i, f in enumerate(fitur, 1):
    print(f"{i}. {f}")

print("=" * 50)