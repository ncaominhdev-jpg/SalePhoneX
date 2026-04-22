<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-gray-900 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">1,250</div>
                        <div class="text-gray-500">Users</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l1 2h13l1-2h2M7 21h10a2 2 0 002-2v-5H5v5a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">320</div>
                        <div class="text-gray-500">Orders</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 10c-4.418 0-8-1.79-8-4V6c0-2.21 3.582-4 8-4s8 1.79 8 4v8c0 2.21-3.582 4-8 4z" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">$7,500</div>
                        <div class="text-gray-500">Revenue</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">8,200</div>
                        <div class="text-gray-500">Visits</div>
                    </div>
                </div>
            </div>

            <!-- Chart & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart Placeholder -->
                <div class="bg-white p-6 rounded-lg shadow col-span-2 flex flex-col items-center justify-center">
                    <h3 class="text-lg font-semibold mb-4">Sales Overview</h3>
                    <img src="https://placehold.co/600x250?text=Chart+Placeholder" alt="Chart" class="rounded-lg border" />
                    <p class="text-gray-400 mt-2">(Bạn có thể tích hợp Chart.js hoặc ApexCharts tại đây)</p>
                </div>
                <!-- Recent Activity -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                    <ul class="divide-y divide-gray-200">
                        <li class="py-2 flex items-center">
                            <span class="h-2 w-2 bg-green-500 rounded-full mr-2"></span>
                            New user <span class="font-semibold mx-1">John Doe</span> registered.
                        </li>
                        <li class="py-2 flex items-center">
                            <span class="h-2 w-2 bg-blue-500 rounded-full mr-2"></span>
                            Order <span class="font-semibold mx-1">#1234</span> has been placed.
                        </li>
                        <li class="py-2 flex items-center">
                            <span class="h-2 w-2 bg-yellow-500 rounded-full mr-2"></span>
                            Payment of <span class="font-semibold mx-1">$250</span> received.
                        </li>
                        <li class="py-2 flex items-center">
                            <span class="h-2 w-2 bg-red-500 rounded-full mr-2"></span>
                            User <span class="font-semibold mx-1">Jane Smith</span> deleted account.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
