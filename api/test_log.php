   <?php
   // Enable error reporting
   ini_set('display_errors', 1);
   ini_set('display_startup_errors', 1);
   error_reporting(E_ALL);
   
   // Test direct file writing first
   $log_file = __DIR__ . '/logs/test_direct.txt';
   file_put_contents($log_file, date('Y-m-d H:i:s') . " - Direct test log entry\n", FILE_APPEND);
   
   echo "Direct file write test completed.<br>";
   
   // Now try the helper functions
   require_once 'helpers.php';
   
   try {
       log_activity('test_activity', ['test' => 'This is a test']);
       echo "Activity logging test completed.<br>";
       
       log_error('test_error', 'This is a test error');
       echo "Error logging test completed.<br>";
   } catch (Exception $e) {
       echo "Exception: " . $e->getMessage();
   }
   
   echo "<br>All tests completed. Check database and logs directory.";
   ?>