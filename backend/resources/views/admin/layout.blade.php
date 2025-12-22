<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GetCha Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-gray-800 p-4 text-white shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="#" class="font-bold text-xl text-yellow-500 flex items-center gap-2">
                <i class="fas fa-coffee"></i> GetCha Admin
            </a>
            
            <div class="flex gap-2">
                {{-- Menu Products --}}
                <a href="{{ route('admin.products.index') }}" 
                   class="px-4 py-2 rounded transition-colors hover:bg-gray-700 hover:text-yellow-500 {{ request()->routeIs('admin.products.*') ? 'text-yellow-500 font-bold bg-gray-900' : '' }}">
                   <i class="fas fa-box mr-1"></i> Products
                </a>

                {{-- Menu Deals --}}
                <a href="{{ route('admin.promos.index') }}" 
                   class="px-4 py-2 rounded transition-colors hover:bg-gray-700 hover:text-yellow-500 {{ request()->routeIs('admin.promos.*') ? 'text-yellow-500 font-bold bg-gray-900' : '' }}">
                   <i class="fas fa-ticket-alt mr-1"></i> Deals
                </a>

                {{-- Menu Orders --}}
                <a href="{{ route('admin.orders.index') }}" 
                   class="px-4 py-2 rounded transition-colors hover:bg-gray-700 hover:text-yellow-500 {{ request()->routeIs('admin.orders.*') ? 'text-yellow-500 font-bold bg-gray-900' : '' }}">
                   <i class="fas fa-shopping-bag mr-1"></i> Orders
                </a>

                {{-- Menu Customers --}}
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 rounded transition-colors hover:bg-gray-700 hover:text-yellow-500 {{ request()->routeIs('admin.users.*') ? 'text-yellow-500 font-bold bg-gray-900' : '' }}">
                   <i class="fas fa-users mr-1"></i> Customers
                </a>

                {{-- 👇 Menu Map Manager (BARU DITAMBAHKAN) --}}
                <a href="{{ route('admin.maps.index') }}" 
                   class="px-4 py-2 rounded transition-colors hover:bg-gray-700 hover:text-yellow-500 {{ request()->routeIs('admin.maps.*') ? 'text-yellow-500 font-bold bg-gray-900' : '' }}">
                   <i class="fas fa-map mr-1"></i> Map Manager
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        
        {{-- 1. ALERT SUKSES (HIJAU) --}}
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        {{-- 2. ALERT ERROR CUSTOM (MERAH) --}}
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
                <strong class="font-bold"><i class="fas fa-exclamation-circle mr-1"></i> Gagal!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        {{-- 3. ALERT ERROR VALIDASI FORM (MERAH LIST) --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
                <strong class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Ups, ada yang salah!</strong>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

</body>
</html>