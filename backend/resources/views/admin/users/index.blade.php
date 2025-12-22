@extends('admin.layout')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Customer Management</h1>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h5 class="text-gray-700 font-bold"><i class="fas fa-users mr-2"></i> Daftar Pelanggan Terdaftar</h5>
        </div>
        
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Poin Saat Ini</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-bold text-navy-900">
                            {{ $user->name }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-500">
                            {{ $user->email }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <span class="bg-yellow-100 text-yellow-800 py-1 px-3 rounded-full text-xs font-bold border border-yellow-200">
                                <i class="fas fa-star mr-1 text-yellow-600"></i> {{ $user->points }} pts
                            </span>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                            <button onclick="openUserModal({{ $user->id }})" class="bg-navy-900 text-white px-3 py-1 rounded hover:bg-blue-800 transition text-xs font-bold">
                                <i class="fas fa-eye mr-1"></i> Detail & Set Poin
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden transform transition-all scale-100">
        <div class="bg-gray-800 px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fas fa-user-circle"></i> <span id="modalUserName">Loading...</span>
            </h3>
            <button onclick="closeUserModal()" class="text-gray-300 hover:text-white focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 text-center">
                    <p class="text-xs text-blue-500 font-bold uppercase">Total Orders</p>
                    <p class="text-2xl font-bold text-blue-900" id="modalTotalOrders">0</p>
                </div>
                <div class="bg-pink-50 p-3 rounded-lg border border-pink-100 text-center">
                    <p class="text-xs text-pink-500 font-bold uppercase">Sering Dipesan</p>
                    <p class="text-sm font-bold text-pink-900 leading-tight mt-1" id="modalFavItem">-</p>
                    <p class="text-[10px] text-pink-400" id="modalFavCount">(0x order)</p>
                </div>
            </div>

            <hr class="border-gray-100 mb-6">

            <form id="pointsForm" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Set Loyalty Points
                    </label>
                    <div class="flex gap-2">
                        <input type="number" name="points" id="modalPointsInput" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required min="0">
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded shadow">
                            Update
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Poin ini akan tampil di aplikasi user.</p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    async function openUserModal(userId) {
        const modal = document.getElementById('userModal');
        const form = document.getElementById('pointsForm');
        
        // 1. Reset Tampilan Modal
        document.getElementById('modalUserName').innerText = "Loading...";
        document.getElementById('modalTotalOrders').innerText = "...";
        document.getElementById('modalFavItem').innerText = "...";
        document.getElementById('modalFavCount').innerText = "";
        
        // 2. Tampilkan Modal
        modal.classList.remove('hidden');

        // URL tujuan (Sesuai web.php)
        const url = `/admin/users/${userId}/stats`;

        try {
            console.log("Fetching URL:", url); // Cek Console browser (F12)

            const response = await fetch(url);

            // 3. Cek Status HTTP (404 = Salah URL, 500 = Error Kodingan/DB)
            if (!response.ok) {
                throw new Error(`Server Error: ${response.status} ${response.statusText}`);
            }

            const data = await response.json();

            // 4. Cek apakah Controller mengirim pesan error spesifik
            if (data.debug_error) {
                console.error("DEBUG ERROR:", data.debug_error);
                // Tampilkan di modal agar terlihat jelas
                document.getElementById('modalFavItem').innerText = "SYSTEM ERROR";
                document.getElementById('modalFavCount').innerText = data.debug_error;
                alert("Ada Masalah Database: " + data.debug_error);
            } else {
                // Jika Sukses
                document.getElementById('modalFavItem').innerText = data.favorite_item;
                document.getElementById('modalFavCount').innerText = `(${data.freq_count}x dipesan)`;
            }

            // Isi data user lainnya
            document.getElementById('modalUserName').innerText = data.user.name;
            document.getElementById('modalTotalOrders').innerText = data.total_orders;
            document.getElementById('modalPointsInput').value = data.user.points;

            // Set Action Form Update Points
            form.action = `/admin/users/${userId}/points`;

        } catch (error) {
            console.error("Javascript Error:", error);
            alert("GAGAL: " + error.message);
            // Jangan tutup modal biar bisa baca errornya di console
        }
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
    }
</script>
@endsection