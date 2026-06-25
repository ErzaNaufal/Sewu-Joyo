from flask import Flask, request, jsonify
from flask_cors import CORS

import os
import numpy as np
import pandas as pd
import joblib

# ==============================
# INIT APP
# ==============================
app = Flask(__name__)
CORS(app)

# ==============================
# LOAD FILE MODEL
# ==============================
base_dir = os.path.dirname(__file__)

try:

    model = joblib.load(
        os.path.join(
            base_dir,
            'model_final.joblib'
        )
    )

    le = joblib.load(
        os.path.join(
            base_dir,
            'encoder_barang.joblib'
        )
    )

    metrics = joblib.load(
        os.path.join(
            base_dir,
            'metrics.pkl'
        )
    )

    mean_barang_dict = joblib.load(
        os.path.join(
            base_dir,
            'mean_barang.pkl'
        )
    )

    freq_barang_dict = joblib.load(
        os.path.join(
            base_dir,
            'freq_barang.pkl'
        )
    )

    print("✅ Semua model berhasil dimuat")

except Exception as e:

    raise Exception(
        f'❌ Gagal load model: {e}'
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

    try:

        # ==============================
        # AMBIL JSON
        # ==============================
        data = request.get_json()

        print("\n==============================")
        print("📥 REQUEST MASUK")
        print(data)

        # ==============================
        # VALIDASI DATA
        # ==============================
        if not data:

            return jsonify({
                'error': 'Data kosong'
            }), 400

        required = [

            'produk',
            'tanggal',
            'lag1',
            'lag2',
            'lag3'

        ]

        for r in required:

            if r not in data:

                return jsonify({
                    'error': f'{r} wajib diisi'
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
        hari_libur = [

            '2026-01-01',

            '2026-03-19',
            '2026-03-20',

            '2026-05-14',

            '2026-05-27',

            '2026-08-17',

            '2026-12-25'

        ]

        is_holiday = int(
            1 if tanggal_str in hari_libur
            else 0
        )

        # ==============================
        # LIBUR BESAR
        # ==============================
        libur_besar = [

            '2026-03-20',
            '2026-03-21',

            '2026-05-27',

            '2026-12-25'

        ]

        is_big_holiday = int(
            1 if tanggal_str in libur_besar
            else 0
        )

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
        # FITUR FINAL (13 FITUR)
        # ==============================
        fitur = np.array([[

            hari,

            bulan,

            minggu_bulan,

            is_weekend,

            is_holiday,

            is_big_holiday,

            lag_1,

            lag_2,

            lag_3,

            diff_1,

            mean_barang,

            freq_barang,

            barang_encoded

        ]])

        print("\n📊 FITUR MODEL:")
        print(fitur)

        # ==============================
        # PREDIKSI MODEL
        # ==============================
        pred = float(
            model.predict(fitur)[0]
        )

        # ==============================
        # STABILISASI
        # ==============================
        avg = (
            lag_1 +
            lag_2 +
            lag_3
        ) / 3

        hasil = (
            pred * 0.7
        ) + (
            avg * 0.3
        )

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

                'big_holiday':
                    is_big_holiday,

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

                'MAE':
                    MAE,

                'RMSE':
                    RMSE,

                'R2':
                    R2

            }

        })

    except Exception as e:

        print("\n❌ ERROR:")
        print(str(e))

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
if __name__ == '__main__':

    app.run(

        host='127.0.0.1',

        port=5000,

        debug=True

    )