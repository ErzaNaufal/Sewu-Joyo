import joblib

fitur = joblib.load("fitur_model.pkl")

print(type(fitur))
print()
print(fitur)