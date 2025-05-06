<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $kategori_id
 * @property string $nama_ikan
 * @property string $slug
 * @property string|null $deskripsi
 * @property int $harga
 * @property int $stok
 * @property string $status_ketersediaan
 * @property string|null $gambar_utama
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\KategoriIkan $kategori
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pesanan> $pesanan
 * @property-read int|null $pesanan_count
 * @method static \Database\Factories\IkanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereGambarUtama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereHarga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereKategoriId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereNamaIkan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereStatusKetersediaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereStok($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ikan whereUpdatedAt($value)
 */
	class Ikan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $nama_kategori
 * @property string $slug
 * @property string|null $deskripsi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ikan> $ikan
 * @property-read int|null $ikan_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan whereNamaKategori($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriIkan whereUpdatedAt($value)
 */
	class KategoriIkan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $ikan_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Ikan $ikan
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem whereIkanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KeranjangItem whereUserId($value)
 */
	class KeranjangItem extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $nama_pelanggan
 * @property string|null $nomor_whatsapp
 * @property string|null $alamat_pengiriman
 * @property int|null $total_harga
 * @property \Illuminate\Support\Carbon|null $tanggal_pesan
 * @property string $status
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $user_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ikan> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\PesananFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereAlamatPengiriman($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereNamaPelanggan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereNomorWhatsapp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereTanggalPesan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereTotalHarga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pesanan whereUserId($value)
 */
	class Pesanan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $profile_photo_public_id
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $initials
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KeranjangItem> $keranjangItems
 * @property-read int|null $keranjang_items_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pesanan> $pesanan
 * @property-read int|null $pesanan_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

