

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        @php
            $users = \App\Models\User::orderBy('name')->get();
        @endphp

        <x-custom.user-table :users="$users" />
    </div>
@endsection
