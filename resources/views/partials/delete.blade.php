<form method="POST" action="{{ $action }}" onsubmit="return confirm('{{ $confirm ?? 'Sei sicuro di voler eliminare?' }}')">
    @csrf
    @method('DELETE')
    <button class="btn small danger" type="submit">Elimina</button>
</form>
