<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customers</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-6xl mx-auto py-10 px-4">

        <div class="bg-white rounded-lg shadow">

            <div class="p-6 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <div>
                    <h1 class="text-2xl font-bold">
                        Customers
                    </h1>

                    <p class="text-gray-500 mt-1">
                        Manage your customers
                    </p>
                </div>

                <a
                    href="{{ route('customers.create') }}"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg text-center"
                >
                    + Add Customer
                </a>

            </div>

            @if (session('success'))
                <div class="mx-6 mt-6 bg-green-100 text-green-800 p-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3">Customer ID</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($customers as $customer)

                            <tr class="border-b hover:bg-gray-50">

                                <td class="px-6 py-4 font-medium">
                                    {{ $customer->customer_code }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $customer->name }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $customer->phone }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $customer->email ?? '-' }}
                                </td>

                                <td class="px-6 py-4">

                                    @if ($customer->is_active)
                                        <span class="text-green-600 font-medium">
                                            Active
                                        </span>
                                    @else
                                        <span class="text-red-600 font-medium">
                                            Inactive
                                        </span>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="5"
                                    class="px-6 py-10 text-center text-gray-500"
                                >
                                    No customers found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>
</html>