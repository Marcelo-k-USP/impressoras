@forelse ($printings as $printing)
    <tr>
        <td>{{ $printing->jobid }}</td>
        @can('admin')
          <td>{{ $printing->user }}</td>
          <td>{{ $printing->host }}</td>
        @endcan
        <td>{{ \Carbon\Carbon::CreateFromFormat('Y-m-d H:i:s', $printing->created_at)->format('d/m/Y H:i') }} </td>
        <td>{{ $printing->pages }}</td>
        <td>{{ $printing->copies }}</td>
        <td>{{ round((float)$printing->filesize/1024) }} MB</td>
        <td>{{ $printing->filename }}</td>
        <td>{{ $printing->printer }}</td>
    </tr>
@empty
        <tr>
            <td colspan="7">Não há impressões</td>
        </tr>
@endforelse

