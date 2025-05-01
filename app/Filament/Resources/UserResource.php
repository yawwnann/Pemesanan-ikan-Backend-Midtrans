<?php
// File: app/Filament/Resources/UserResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash; // <-- Import Hash
use Filament\Pages\Page; // <-- Import Page

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pengguna Admin'; // Label Navigasi
    protected static ?string $modelLabel = 'Pengguna Admin'; // Label Model Tunggal
    protected static ?string $pluralModelLabel = 'Pengguna Admin'; // Label Model Jamak
    protected static ?string $navigationGroup = 'Pengaturan'; // Contoh Grup

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true) // Unik, abaikan record saat ini (untuk edit)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Password Baru')
                    ->password() // Input type password
                    // Hanya required saat membuat user baru
                    ->required(fn(Page $livewire): bool => $livewire instanceof Pages\CreateUser)
                    // Nonaktifkan requirement saat edit (hanya isi jika ingin ganti)
                    ->visibleOn('create') // Hanya visible di halaman create by default (lihat bawah)
                    ->dehydrateStateUsing(fn($state) => Hash::make($state)) // Hash password saat disimpan
                    ->dehydrated(fn($state) => filled($state)) // Hanya proses jika field diisi
                    ->maxLength(255),
                // Untuk Edit: Tampilkan field password baru secara terpisah jika perlu
                TextInput::make('new_password')
                    ->label('Password Baru (Edit)')
                    ->password()
                    ->nullable() // Tidak wajib diisi saat edit
                    ->visibleOn('edit') // Hanya tampil saat edit
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state)) // Hanya proses jika field diisi
                    ->helperText('Isi hanya jika ingin mengubah password.'),
                TextInput::make('new_password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->same('new_password') // Validasi harus sama dengan new_password
                    ->requiredWith('new_password') // Wajib jika new_password diisi
                    ->visibleOn('edit'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default
            ])
            ->filters([
                // Filter bisa ditambahkan di sini jika perlu
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation manager bisa ditambahkan di sini
        ];
    }

    // --- BAGIAN PENTING UNTUK ROUTE ---
    // Menggunakan struktur standar List, Create, Edit pages
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),       // Halaman Daftar
            'create' => Pages\CreateUser::route('/create'), // Halaman Buat
            'edit' => Pages\EditUser::route('/{record}/edit'), // Halaman Edit
        ];
    }
    // --- AKHIR BAGIAN PENTING ---
}