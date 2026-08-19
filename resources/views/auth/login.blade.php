<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Masuk | Kaizen Tracker</title>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm">
        <div class="bg-white p-8 rounded-sm border border-slate-200 shadow-sm">
            <div class="mb-8 text-center">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kaizen Tracker</h1>
                <p class="text-xs text-slate-500 font-medium uppercase tracking-widest mt-1">Pelacak Perbaikan Mingguan</p>
            </div>

            <h2 class="text-lg font-bold text-slate-900 mb-6">Masuk</h2>

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-sm text-sm focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all @error('email') border-red-500 @enderror"
                        placeholder="your@email.com">
                    @error('email')
                        <p class="text-[10px] text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Kata Sandi</label>
                    <input type="password" name="password" required
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-sm text-sm focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-sm font-bold text-sm tracking-wide hover:bg-blue-700 transition-all active:scale-[0.98]">
                        Masuk
                    </button>
                </div>
            </form>
        </div>
        <p class="text-center text-[10px] text-slate-400 mt-6 font-medium">Alat Operasional Internal • Peroniks Group</p>
    </div>
</body>
</html>
