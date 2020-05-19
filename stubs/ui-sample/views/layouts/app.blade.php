@extends('{$addon_name}::layouts.default')

@section('meta')
{{-- meta --}}
@endsection

@push('styles')
{{-- CSS --}}
	<link rel="stylesheet" href="https://cdn.bootcss.com/twitter-bootstrap/4.3.1/css/bootstrap.min.css">
@endpush

@push('scripts')
{{-- JavaScript --}}
    <script src="https://cdn.bootcss.com/jquery/2.2.0/jquery.min.js"></script>
	<script src="https://cdn.bootcss.com/twitter-bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>
@endpush
