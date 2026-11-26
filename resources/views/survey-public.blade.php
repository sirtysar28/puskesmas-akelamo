<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#076e3b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>Survei Kepuasan | Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-puskesmas.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
</head>
<body class="survey-body">
<div class="survey-shell">
    <div class="survey-box anim-up">
        <div class="survey-brand">
            <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="Logo Puskesmas Akelamo">
            <div>
                <strong>Puskesmas Akelamo</strong>
                <small>Survei Kepuasan Masyarakat</small>
            </div>
        </div>

        <div id="survey-area">
            <h1>Bagaimana layanan kami?</h1>
            <p>Penilaian Anda membantu kami meningkatkan kualitas pelayanan kesehatan. Survei ini anonim dan tidak meminta data pribadi apa pun.</p>

            <form id="survey-form" method="POST" action="{{ route('survey.submit') }}">
                @csrf
                <div class="field">
                    <label for="service">Jenis Layanan</label>
                    <select id="service" name="service" required>
                        @foreach ($services as $service)
                            <option value="{{ $service }}">{{ $service }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Nilai Pelayanan</label>
                    <div class="rating-choice">
                        @foreach (range(1, 5) as $value)
                            <label>
                                <input type="radio" name="rating" value="{{ $value }}" @checked($value === 5)>
                                <span>{{ ['😕','😐','🙂','😃','🤩'][$value - 1] }}</span>
                                <small>{{ ['Kurang','Cukup','Baik','Sangat Baik','Luar Biasa'][$value - 1] }}</small>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="field">
                    <label for="suggestion">Kritik &amp; Saran (opsional)</label>
                    <textarea id="suggestion" name="suggestion" maxlength="1000"
                              placeholder="Tulis masukan Anda di sini (jangan memuat data medis)..."></textarea>
                </div>

                {{-- Honeypot anti-bot: disembunyikan dari manusia --}}
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-error" id="feedback" role="alert"></div>

                <button class="btn btn-survey" type="submit">Kirim Penilaian ✓</button>
            </form>
        </div>

        <footer class="survey-footer">
            © {{ date('Y') }} Puskesmas Akelamo · Developed by
            <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
        </footer>
    </div>
</div>
</body>
</html>
