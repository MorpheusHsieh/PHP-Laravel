<!DOCTYPE html>
<html lang="en">
<head>
	<title>Upload Post</title>
	<meta charset="UTF-8">
</head>
<body>

	@if ($result = Session::get('result'))
    <strong>Result: </strong> {{ $result }}
	@endif
	{!! Session::forget('result') !!}

	<p></p>
  <a href="{{ URL::to('phpspreadsheet/downloadExcel') }}">
		<button class="btn btn-success">Download Excel Sheets</button>
	</a>

	<p></p>
	<form action="{{ URL::to('phpspreadsheet/uploadExcel/sheets') }}" method="post" enctype="multipart/form-data">
		{{ csrf_field() }}
		<input  type="file" name="import_file" />
		<button class="btn btn-primary">Upload Excel File</button>
	</form>

</body>
</html>