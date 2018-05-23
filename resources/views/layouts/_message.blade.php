@foreach(['success', 'message', 'danger', 'warning'] as $msg)
    @if(session()->has($msg))
        <div class="alert alert-{{ $msg }}">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session()->get($msg) }}
        </div>
    @endif
@endforeach