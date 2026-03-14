<?php

return [
    // Authentication & Authorization
    'unauthenticated' => 'Anda harus login terlebih dahulu.',
    'forbidden' => 'Anda tidak memiliki akses.',

    // Not Found
    'notFound' => 'Data tidak ditemukan.',
    'emptyLoadedName' => ':name tidak ditemukan.',

    // Server Errors
    'serverError' => 'Terjadi kesalahan pada server.',
    'databaseError' => 'Terjadi kesalahan pada database.',

    // Method & Request Errors
    'methodNotAllowed' => 'Metode tidak diizinkan.',
    'fileTooLarge' => 'Ukuran file terlalu besar.',
    'sessionExpired' => 'Sesi telah habis. Silakan login kembali.',
    'tooManyRequests' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',

    // Transaction
    'mustUseTransaction' => 'Metode ini harus dipanggil dalam DB::transaction untuk menjaga konsistensi data.',
    'transactionRequired' => 'Operasi ini harus dijalankan dalam transaksi database. Silakan hubungi administrator.',
    'transactionRequiredForOperation' => 'Operasi :operation memerlukan transaksi database. Pastikan metode dipanggil dalam DB::transaction.',

    // Success Messages
    'successLoaded' => 'Berhasil memuat data.',
    'successSaved' => 'Berhasil menyimpan data.',
    'successUpdated' => 'Berhasil mengubah data.',
    'successDeleted' => 'Berhasil menghapus data.',
    'failedSaved' => 'Terjadi kesalahan menyimpan data.',
    'failedUpdated' => 'Terjadi kesalahan ketika mengubah data.',
    'failedDeleted' => 'Terjadi kesalahan ketika menghapus data.',
    'emptyLoaded' => 'Data tidak tersedia.',

    // Validation Messages
    'moreError' => ' (dan :count kesalahan lainnya).',
    'duplicate' => ':attribute sudah digunakan.',

    // Auth & Validation Messages
    'invalidLogin' => 'Username atau Password tidak valid.',
    'successLogin' => 'Detail login berhasil.',
];