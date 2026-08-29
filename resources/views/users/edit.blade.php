@extends('layouts.app')

@section('title', 'Kaizen Tracker | Edit Personel')

@section('content')
<div class="p-6 flex justify-center">
    <div class="w-full max-w-2xl space-y-6">
        <!-- Header -->
        <div class="border-b border-slate-200 pb-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span>OPERASI</span>
                    <span>/</span>
                    <a href="{{ route('users.index') }}" class="hover:underline">KELOLA PERSONEL</a>
                    <span>/</span>
                    <span class="text-secondary">EDIT DATA</span>
                </div>
                <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">Edit Data Personel</h2>
                <p class="text-xs text-slate-400 mt-0.5">Perbarui profil atau penugasan organisasi untuk <strong>{{ $user->name }}</strong>.</p>
            </div>
            <a href="{{ route('users.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-bold uppercase tracking-wider transition-colors">
                Kembali
            </a>
        </div>

        @if($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 rounded text-red-700 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">error</span> Terdapat kesalahan input:
            </div>
            <ul class="list-disc list-inside pl-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white border border-slate-200 rounded-sm shadow-sm p-6">
            <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- Row 1: Name & Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none"/>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Alamat Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none"/>
                    </div>
                </div>

                <!-- Row 2: Password (Optional) & Role -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Ganti Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" placeholder="Minimal 6 karakter baru" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none"/>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Role Sistem <span class="text-red-500">*</span></label>
                        <select name="role" required class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none">
                            <option value="spv" {{ old('role', $user->role) === 'spv' ? 'selected' : '' }}>SPV (Supervisor)</option>
                            <option value="kabag" {{ old('role', $user->role) === 'kabag' ? 'selected' : '' }}>KABAG (Kepala Bagian)</option>
                            <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>MANAGER</option>
                            <option value="director" {{ old('role', $user->role) === 'director' ? 'selected' : '' }}>DIRECTOR</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>ADMIN (Full Access)</option>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Department -->
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Departemen</label>
                    <select name="department_id" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none">
                        <option value="">-- Pilih Departemen --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Section: Area Assignment -->
                <div class="border-t border-slate-100 pt-4 mt-2">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Penugasan Area Aktif</h3>
                    <p class="text-[10px] text-slate-400 mb-3">Mengubah area akan secara otomatis menutup penugasan lama dan mencatat riwayat perpindahan.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Area Penugasan</label>
                            <select name="area_id" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none">
                                <option value="">-- Tanpa Penugasan Area --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id', $activeAssignment?->area_id) == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }} ({{ $area->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Posisi di Area</label>
                            <select name="position" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-3 py-2 text-xs text-slate-800 outline-none">
                                <option value="">Sesuai Role Sistem</option>
                                <option value="spv" {{ old('position', $activeAssignment?->role?->value) === 'spv' ? 'selected' : '' }}>SPV</option>
                                <option value="kabag" {{ old('position', $activeAssignment?->role?->value) === 'kabag' ? 'selected' : '' }}>KABAG</option>
                                <option value="manager" {{ old('position', $activeAssignment?->role?->value) === 'manager' ? 'selected' : '' }}>MANAGER</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded text-xs font-bold uppercase tracking-wider transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-5 py-2 bg-[#0066B3] hover:bg-[#005292] text-white rounded text-xs font-bold uppercase tracking-wider transition-colors shadow-sm flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
