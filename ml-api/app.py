from flask import Flask, request, jsonify
from flask_cors import CORS

import os
import numpy as np
import pandas as pd
import joblib
import sys
import traceback
import holidays

# ==============================
# INIT APP
# ==============================
app = Flask(__name__)
CORS(app)

# ==============================
# LOAD FILE MODEL
# ==============================
base_dir = os.path.dirname(__file__)

print("FILE YANG DIJALANKAN:", __file__)
try:

    model = joblib.load(
        os.path.join(
            base_dir,
            "model_final.joblib"
        )
    )

    le = joblib.load(
        os.path.join(
            base_dir,
            "encoder_barang.joblib"
        )
    )

    metrics = joblib.load(
        os.path.join(
            base_dir,
            "metrics.pkl"
        )
    )

    mean_barang_dict = joblib.load(
        os.path.join(
            base_dir,
            "mean_barang.pkl"
        )
    )

    freq_barang_dict = joblib.load(
        os.path.join(
            base_dir,
            "freq_barang.pkl"
        )
    )

    fitur_model = joblib.load(
        os.path.join(
            base_dir,
            "fitur_model.pkl"
        )
    )

    print("✅ Semua file berhasil dimuat")

    print("\n" + "=" * 50)
    print("INFORMASI MODEL")
    print("=" * 50)

    print(f"Jumlah fitur model : {model.n_features_in_}")
    print(f"Jumlah fitur file  : {len(fitur_model)}")

    print("\nDaftar fitur model:")

    for i, fitur in enumerate(fitur_model, 1):
        print(f"{i}. {fitur}")

    print("=" * 50)

except Exception as e:

    raise Exception(
        f"❌ Gagal load model: {e}"
    )
# ==============================
# METRICS
# ==============================
MAE = round(
    metrics.get('mae', 0),
    2
)

RMSE = round(
    metrics.get('rmse', 0),
    2
)

R2 = round(
    metrics.get('r2', 0),
    2
)

# ==============================
# REKOMENDASI
# ==============================
def get_rekomendasi(pred):

    if pred >= 80:

        return (
            'Tinggi',
            'Tambah stok segera'
        )

    elif pred <= 20:

        return (
            'Rendah',
            'Kurangi pembelian'
        )

    return (
        'Normal',
        'Stok aman'
    )

# ==============================
# HOME
# ==============================
@app.route('/')
def home():

    return jsonify({

        'status': 'API aktif',

        'model': 'Random Forest',

        'metrics': {

            'MAE': MAE,
            'RMSE': RMSE,
            'R2': R2

        }

    })

# ==============================
# METRICS
# ==============================
@app.route('/metrics')
def get_metrics():

    print(">>> ENDPOINT /metrics DIPANGGIL <<<")

    return jsonify({

        "mae": round(metrics.get("mae", 0), 2),
        "mse": round(metrics.get("mse", 0), 2),
        "rmse": round(metrics.get("rmse", 0), 2),
        "r2": round(metrics.get("r2", 0), 4)

    })
# ==============================
# LIST PRODUK
# ==============================
@app.route('/produk')
def produk():

    try:

        produk_list = [

            str(p)
            .strip()
            .lower()

            for p in le.classes_

        ]

        return jsonify({

            'total_produk':
                len(produk_list),

            'produk':
                produk_list

        })

    except Exception as e:

        return jsonify({
            'error': str(e)
        }), 500

# ==============================
# PREDICT
# ==============================
@app.route('/predict', methods=['POST'])
def predict():

    

    print("=" * 50)
    print("Python :", sys.executable)
    print("Holidays :", holidays.__file__)
    print("=" * 50)

    try:

        # ==============================
        # AMBIL JSON
        # ==============================
        data = request.get_json()

        print("\n==============================")
        print("📥 REQUEST MASUK")
        print(data)

        print("\n========== REQUEST DARI LARAVEL ==========")
        print(data)
        print("=========================================\n")

        # ==============================
        # VALIDASI DATA
        # ==============================
        if not data:

            return jsonify({
                "success": False,
                "error": "Data request kosong"
            }), 400

        # Field yang wajib diterima dari Laravel
        required = [

            "produk",
            "tanggal",

            "lag1",
            "lag2",
            "lag3",

            "rolling_mean_7",
            "rolling_std_7",
            "rolling_max_7",
            "rolling_min_7"

        ]

        # Validasi field
        for field in required:

            if field not in data:

                return jsonify({

                    "success": False,
                    "error": f"Field '{field}' wajib dikirim"

                }), 400

            # Cek nilai kosong
            if data[field] is None:

                return jsonify({

                    "success": False,
                    "error": f"Field '{field}' tidak boleh null"

                }), 400

            if isinstance(data[field], str) and data[field].strip() == "":

                return jsonify({

                    "success": False,
                    "error": f"Field '{field}' tidak boleh kosong"

                }), 400
        # ==============================
        # PRODUK
        # ==============================
        produk = str(
            data['produk']
        ).strip().lower()

        produk_valid = [

            str(p)
            .strip()
            .lower()

            for p in le.classes_

        ]

        if produk not in produk_valid:

            return jsonify({

                'error':
                    'Produk tidak dikenali',

                'produk_input':
                    produk,

                'sample_produk':
                    produk_valid[:10]

            }), 400

        # ==============================
        # TANGGAL
        # ==============================
        tanggal = pd.to_datetime(
            data['tanggal']
        )

        tanggal_str = tanggal.strftime(
            '%Y-%m-%d'
        )

        # ==============================
        # FITUR WAKTU
        # ==============================
        hari = int(
            tanggal.dayofweek
        )

        bulan = int(
            tanggal.month
        )

        minggu_bulan = int(
            ((tanggal.day - 1) // 7) + 1
        )

        is_weekend = int(
            1 if hari in [5, 6]
            else 0
        )

        # ==============================
        # HARI LIBUR
        # ==============================

        try:
            id_holidays = holidays.Indonesia()
            is_holiday = int(tanggal in id_holidays)
        except Exception as e:
            print("IMPORT HOLIDAYS ERROR:", repr(e))
            raise

        # ==============================
        # LAG FEATURE
        # ==============================
        lag_1 = max(
            0,
            float(data['lag1'])
        )

        lag_2 = max(
            0,
            float(data['lag2'])
        )

        lag_3 = max(
            0,
            float(data['lag3'])
        )

        # ==============================
        # TREND
        # ==============================
        diff_1 = (
            lag_1 - lag_2
        )

        # ==============================
        # ROLLING FEATURE
        # ==============================

        try:

            rolling_mean_7 = max(
                0,
                float(data.get("rolling_mean_7", lag_1))
            )

            rolling_std_7 = max(
                0,
                float(data.get("rolling_std_7", 0))
            )

            rolling_max_7 = max(
                0,
                float(data.get("rolling_max_7", lag_1))
            )

            rolling_min_7 = max(
                0,
                float(data.get("rolling_min_7", lag_1))
            )

        except (TypeError, ValueError):

            # Fallback apabila Laravel tidak mengirim rolling feature
            histori = [lag_1, lag_2, lag_3]

            rolling_mean_7 = float(np.mean(histori))
            rolling_std_7 = float(np.std(histori))
            rolling_max_7 = float(np.max(histori))
            rolling_min_7 = float(np.min(histori))

        # ==============================
        # MEAN BARANG
        # ==============================
        mean_barang = float(

            mean_barang_dict.get(

                produk,

                (
                    lag_1 +
                    lag_2 +
                    lag_3
                ) / 3

            )

        )

        # ==============================
        # FREKUENSI BARANG
        # ==============================
        freq_barang = int(

            freq_barang_dict.get(
                produk,
                1
            )

        )

        # ==============================
        # ENCODING PRODUK
        # ==============================
        original_produk = None

        for p in le.classes_:

            if str(p).strip().lower() == produk:

                original_produk = p
                break

        barang_encoded = int(

            le.transform(
                [original_produk]
            )[0]

        )

        # ==============================
        # FITUR FINAL (16 FITUR)
        # ==============================
        X = pd.DataFrame([{

            "hari": hari,

            "bulan": bulan,

            "minggu_bulan": minggu_bulan,

            "is_weekend": is_weekend,

            "is_holiday": is_holiday,

            "lag_1": lag_1,

            "lag_2": lag_2,

            "lag_3": lag_3,

            "diff_1": diff_1,

            "mean_barang": mean_barang,

            "rolling_mean_7": rolling_mean_7,

            "rolling_std_7": rolling_std_7,

            "rolling_max_7": rolling_max_7,

            "rolling_min_7": rolling_min_7,

            "freq_barang": freq_barang,

            "barang_encoded": barang_encoded

        }])

        # Pastikan urutan kolom sama seperti saat training
        X = X[fitur_model]

        print("\n📊 FITUR MODEL")
        print(X)

        # ==============================
        # PREDIKSI MODEL + STABILISASI
        # ==============================
        print("\n========== INPUT MODEL ==========")
        print(X.to_string())
        print("================================")
        
        pred = float(model.predict(X)[0])

        print(f"\nPrediksi Random Forest : {pred}")

        avg = (
            lag_1 +
            lag_2 +
            lag_3
        ) / 3

        print(f"Rata-rata Lag : {avg}")

        hasil = (
            pred * 0.7
        ) + (
            avg * 0.3
        )

        print(f"Hasil Setelah Stabilisasi : {hasil}")

        # ==============================
        # BOOST LIBUR
        # ==============================
        if is_holiday == 1:

            hasil = hasil * 1.15

        # ==============================
        # MINIMAL NILAI
        # ==============================
        hasil = max(
            0,
            hasil
        )

        # ==============================
        # KATEGORI
        # ==============================
        kategori, rekomendasi = (
            get_rekomendasi(hasil)
        )

        print("\n✅ HASIL PREDIKSI:")
        print(round(hasil, 2))

        # ==============================
        # RESPONSE
        # ==============================
        return jsonify({

            'success': True,

            'produk':
                produk,

            'tanggal':
                tanggal_str,

            'prediksi':
                round(
                    hasil,
                    2
                ),

            'kategori':
                kategori,

            'rekomendasi':
                rekomendasi,

            'fitur': {

                'hari':
                    hari,

                'bulan':
                    bulan,

                'minggu_bulan':
                    minggu_bulan,

                'weekend':
                    is_weekend,

                'holiday':
                    is_holiday,

                'lag1':
                    lag_1,

                'lag2':
                    lag_2,

                'lag3':
                    lag_3,

                'diff_1':
                    diff_1,

                'mean_barang':
                    round(mean_barang, 2),

                'freq_barang':
                    freq_barang,

                'barang_encoded':
                    barang_encoded

            },

            'metrics': {
                'MAE': round(metrics['mae'], 2),
                'RMSE': round(metrics['rmse'], 2),
                'R2': round(metrics['r2'], 4)
            }

        })

    except Exception as e:

        print("\n========== ERROR ==========")
        traceback.print_exc()
        print("===========================")

        return jsonify({

            'success': False,

            'error': str(e)

        }), 500

# ==============================
# TEST API
# ==============================
@app.route('/test')
def test():

    return jsonify({

        'status': 'OK',

        'message':
            'API berjalan normal'

    })

# ==============================
# RUN APP
# ==============================
print("\n=== DAFTAR ROUTE ===")
for rule in app.url_map.iter_rules():
    print(rule)
print("====================\n")
print(">>> ROUTE /metrics BERHASIL DIMUAT <<<")
if __name__ == "__main__":
    app.run(
        host="0.0.0.0",
        port=int(os.environ.get("PORT", 5000))
    )