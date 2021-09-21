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
<td>
@php 
if (strpos($printing->filename, 'smbprn.') !== false) {
  $filename = substr(explode(' ', $printing->filename, 2)[1],0,28);
}
else {
  $filename = substr($printing->filename,0,28);                    
}
echo utf8_decode($filename) . " ...";
@endphp
</td>

@can('admin')
<td><a href="/printings/{{ $printing->printer }}">{{ $printing->printer }}</a></td>
@else
<td>{{ $printing->printer }}</td>
@endcan
<td>{{ $printing->status }}</td>
</tr>

