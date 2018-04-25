<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use App\Models\Post;
use Log;
use Storage;

class DownloadUploadController extends Controller
{
  public function uploadExcel(Request $request, $tableName)
  {
    $inputFile = $request->file('import_file');
    $input_filename = $inputFile->getClientOriginalName();
    $logs = array('filename' => $input_filename);

    $reader = new Reader();
    $spreadsheet = $reader->load($inputFile);
    $sheetnames = $spreadsheet->getSheetNames();
    $sheetCnt = $spreadsheet->getSheetCount();

    $host = "10.10.1.197";
    $username = "nexcom";
    $password = "nexcom";
    $dbname = "laravel-excel";

    // Check connection
    $conn = mysqli_connect($host, $username, $password, $dbname);
    if (!$conn) {
      echo "Error: Unable to connect to MySQL." . PHP_EOL;
      echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
      echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
      exit;
    }

    $charset = mysqli_character_set_name($conn);
    // log::info($charset);

    mysqli_set_charset($conn, "utf8");

    $charset = mysqli_character_set_name($conn);
    // log::info($charset);

    mysqli_begin_transaction($conn);

    // $tableName = 'post';

    foreach ($sheetnames as $i => $sheetname)
    {
      $spreadsheet->setActiveSheetIndexByName($sheetname);
      $sheetData = $spreadsheet->getActiveSheet()->toArray();
      $sqlSet = $this->dataSetToSqlSet($tableName, $sheetname, $sheetData);

      $cntSuccess = 0;
      $cntFailure = 0;
      $isCommit = true;
      foreach ($sqlSet as $j => $sqlstr)
      {
        $isQuery = mysqli_query($conn, $sqlstr);
        $isCommit = $isCommit && $isQuery;

        if ($isQuery === false) {
          $cntFailure++;
          log::info($sqlstr);
          log::info("Errorcode: " . mysqli_errno($conn));
        } else {
          $cntSuccess ++;
        }
      }

      if ($isCommit) {
        mysqli_commit($conn);
      }
      else {
        mysqli_rollback($conn);
      }
      $logs['sheets'][$sheetname] = array(
        'success' => $cntSuccess,
        'failure' => $cntFailure
      );
    }

    mysqli_close($conn);

    $request->session()->put('result', json_encode($logs, JSON_UNESCAPED_UNICODE));

    return back();
  }

  // ---------------------------------------------------------------------------

  public function dataSetToSqlSet($tableName, $sheetName, $dataSet)
  {
    // 找出 excel column 的資料型態
    $fieldNames = $dataSet[0];
    $fieldStr = "`sheetname`,`row_no`,`col_no`,`value`";
    // log::info($fieldStr);

    $sqlSet = array();
    $sqlstr_tmp = "INSERT INTO $tableName ($fieldStr) VALUES (%valueStr%);";

    // dataSet to sqlSet
    $numcols = count($fieldNames);
    $numrows = count($dataSet);
    for ($i=1; $i<$numrows; $i++)
    {
      $row_no = $i;

      $sqlstr  = '';
      $valueStr = '';

      $rowSet = $dataSet[$i];
      if (!$rowSet[0]) continue;

      for ($j=0; $j<$numcols; $j++)
      {
        $col_no = $this->getExcelColumnNames($j);

        $value = trim($rowSet[$j]);
        $value = str_replace(',', '\,', $value);
        $value = str_replace("'", "\'", $value);

        $valueStr = "'$sheetName','$row_no','$col_no','$value'";
        $sqlstr = str_replace("%valueStr%", $valueStr, $sqlstr_tmp);
        $sqlSet[] = $sqlstr;
      }
    }

    return $sqlSet;
  }

  // ---------------------------------------------------------------------------

  public function downloadExcel()
  {
    $post = new Post;
    $fileName = 'post.xlsx';
    // log::info($table);

    $columns = $post->getTableColumns();
    // log::info($columns);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('sheet 1');

    $rownum = 1;

    foreach($columns as $i => $field) {
      $colStr = $this->getExcelColumnNames($i) . $rownum;
      $sheet->setCellValue($colStr, $field);
    }

    $dataSet = $post->all();
    foreach ($dataSet as $rowJson)
    {
      $rownum++;
      $colnum = 0;
      foreach (json_decode($rowJson) as $key => $cell)
      {
        // log::info($key.', '.$cell);
        $colStr = $this->getExcelColumnNames($colnum) . $rownum;
        $sheet->setCellValue($colStr, $cell);
        $colnum++;
      }
    }

    $writer = new Writer($spreadsheet);
    $writer->save($fileName);

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    return back();
  }

  // ---------------------------------------------------------------------------

  private function getExcelColumnNames($num)
  {
    $numeric = $num % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval($num / 26);
    if ($num2 > 0) {
        return getNameFromNumber($num2 - 1) . $letter;
    } else {
        return $letter;
    }
  }

  // ---------------------------------------------------------------------------


}
