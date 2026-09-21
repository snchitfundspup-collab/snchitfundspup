<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Customer</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-2xl mx-auto py-10 px-4">

        <div class="bg-white rounded-lg shadow p-6">

            <h1 class="text-2xl font-bold mb-6">
                Add Customer
            </h1>

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('customers.store') }}">

                @csrf

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full border rounded px-3 py-2"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        class="w-full border rounded px-3 py-2"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Address
                    </label>

                    <textarea
                        name="address"
                        rows="3"
                        class="w-full border rounded px-3 py-2"
                    >{{ old('address') }}</textarea>
                </div>

                <div class="mb-6">
                    <label class="block font-medium mb-1">
                        Remarks / Identification
                    </label>

                    <textarea
                        name="remarks"
                        rows="3"
                        class="w-full border rounded px-3 py-2"
                    >{{ old('remarks') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded"
                >
                    Save Customer
                </button>

            </form>

        </div>

    </div>

</body>
</html>