<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
<table border="1">
    <tr>
        <th colspan="{{ count($selectedColumns) }}">Custom Student Report {{ $sessionName ? '- '.$sessionName : '' }}</th>
    </tr>
    <tr>
        @foreach($selectedColumns as $key)
            <th>{{ $columns[$key]['label'] }}</th>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $value)
                <td>{{ $value }}</td>
            @endforeach
        </tr>
    @endforeach
</table>
</body>
</html>
