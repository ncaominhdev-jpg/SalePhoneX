@if (isset($data))
    <script>
        window.filamentData = @js($data)
    </script>
@endif

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@stack('scripts')

@foreach ($assets as $asset)
    @if (! $asset->isLoadedOnRequest())
        {{ $asset->getHtml() }}
    @endif
@endforeach

<style>
    :root {
        @foreach ($cssVariables ?? [] as $cssVariableName => $cssVariableValue) --{{ $cssVariableName }}:{{ $cssVariableValue }}; @endforeach
    }
</style>
