<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriIkanResource\Pages;
use App\Filament\Resources\KategoriIkanResource\RelationManagers;
use App\Models\KategoriIkan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str; // Import Str facade
use Filament\Forms\Set; // Import Set

class KategoriIkanResource extends Resource
{
    protected static ?string $model = KategoriIkan::class;

    // (Opsional) Ganti ikon navigasi
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    // (Opsional) Ganti label model
    protected static ?string $modelLabel = 'Kategori Ikan';
    protected static ?string $pluralModelLabel = 'Kategori Ikan';

    // (Opsional) Kelompokkan di navigasi
    protected static ?string $navigationGroup = 'Manajemen Katalog';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_kategori')
                    ->required()
                    ->maxLength(100)
                    // Auto generate slug saat nama diketik
                    ->live(debounce: 500)
                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(KategoriIkan::class, 'slug', ignoreRecord: true)
                    ->maxLength(120),
                Forms\Components\Textarea::make('deskripsi')
                    ->nullable()
                    ->columnSpanFull(), // Agar lebar penuh
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_kategori')
                    ->searchable() // Aktifkan pencarian
                    ->sortable(),  // Aktifkan pengurutan
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('ikan_count') // Tampilkan jumlah ikan per kategori
                    ->counts('ikan') // 'ikan' adalah nama relasi di model KategoriIkan
                    ->label('Jumlah Ikan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan defaultnya
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tambahkan filter jika perlu
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
            // Jika ingin menampilkan daftar ikan terkait langsung di halaman edit kategori
            // RelationManagers\IkanRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKategoriIkans::route('/'),
            'create' => Pages\CreateKategoriIkan::route('/create'),
            'edit' => Pages\EditKategoriIkan::route('/{record}/edit'),
        ];
    }
}