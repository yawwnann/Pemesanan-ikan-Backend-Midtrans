<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IkanResource\Pages;
use App\Filament\Resources\IkanResource\RelationManagers;
use App\Models\Ikan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Forms\Set;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn; // <-- Import untuk TextColumn (opsional tapi baik)
use Filament\Tables\Columns\ImageColumn; // <-- Import (opsional tapi baik)
use Filament\Tables\Filters\SelectFilter; // <-- Import (opsional tapi baik)
use Filament\Tables\Actions\EditAction; // <-- Import (opsional tapi baik)
use Filament\Tables\Actions\DeleteAction; // <-- Import (opsional tapi baik)
use Filament\Tables\Actions\BulkActionGroup; // <-- Import (opsional tapi baik)
use Filament\Tables\Actions\DeleteBulkAction; // <-- Import (opsional tapi baik)


class IkanResource extends Resource
{
    protected static ?string $model = Ikan::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $modelLabel = 'Ikan';
    protected static ?string $pluralModelLabel = 'Daftar Ikan';
    protected static ?string $navigationGroup = 'Manajemen Katalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('kategori_id')
                    ->relationship('kategori', 'nama_kategori')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Kategori Ikan'),
                Forms\Components\TextInput::make('nama_ikan')
                    ->required()
                    ->maxLength(150)
                    ->live(debounce: 500)
                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(Ikan::class, 'slug', ignoreRecord: true)
                    ->maxLength(170),
                Forms\Components\RichEditor::make('deskripsi') // <-- Pemanggilan sudah benar
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                // === TAMBAHKAN FIELD STOK ===
                Forms\Components\TextInput::make('stok')
                    ->required()
                    ->numeric() // Hanya menerima angka
                    ->minValue(0) // Stok tidak bisa negatif
                    ->default(0), // Nilai default saat membuat baru
                // === AKHIR FIELD STOK ===
                Forms\Components\Select::make('status_ketersediaan')
                    ->options([
                        'Tersedia' => 'Tersedia',
                        'Habis' => 'Habis',
                        'Pre-Order' => 'Pre-Order',
                    ])
                    ->required()
                    ->default('Tersedia'),
                Forms\Components\FileUpload::make('gambar_utama')
                    ->label('Gambar Utama')
                    ->image()
                    ->directory('ikan-images')
                    ->visibility('public')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ImageColumn::make('gambar_utama') 
                //     ->label('Gambar')
                //     ->disk('public')
                //     ->width(80)
                //     ->height(60),
                TextColumn::make('nama_ikan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('harga')
                    ->money('IDR')
                    ->sortable(),
                // === TAMBAHKAN KOLOM STOK ===
                TextColumn::make('stok')
                    ->numeric() // Format sebagai angka
                    ->sortable(),// Bisa diurutkan
                // === AKHIR KOLOM STOK ===
                TextColumn::make('status_ketersediaan')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Tersedia' => 'success',
                        'Habis' => 'danger',
                        'Pre-Order' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->relationship('kategori', 'nama_kategori')
                    ->label('Filter Kategori'),
                SelectFilter::make('status_ketersediaan')
                    ->options([
                        'Tersedia' => 'Tersedia',
                        'Habis' => 'Habis',
                        'Pre-Order' => 'Pre-Order',
                    ])
                    ->label('Filter Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIkans::route('/'),
            'create' => Pages\CreateIkan::route('/create'),
            'edit' => Pages\EditIkan::route('/{record}/edit'),
        ];
    }
}