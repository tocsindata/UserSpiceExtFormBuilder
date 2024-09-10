<?php
// Function to find the primary field of a table
if (!function_exists('FindPrime')) {
    function FindPrime($database, $table)
    {
        $db = DB::getInstance(); // Get the UserSpice database instance
        $primary_field = '';

        // Get table structure
        $sql1 = "DESCRIBE `$database`.`$table`";
        $db->query($sql1);
        $result1 = $db->results();
        $auto_increment_found = false;
        $unique_found = false;

        foreach ($result1 as $row) {
            $field = $row->Field;
            $key = $row->Key;
            $extra = $row->Extra;

            // Check for an AUTO_INCREMENT primary key (most common)
            if ($key == 'PRI' && strpos($extra, 'auto_increment') !== false) {
                $primary_field = $field;
                $auto_increment_found = true;
                break; // Priority to AUTO_INCREMENT field
            }
            // If no AUTO_INCREMENT, check for any primary key
            elseif ($key == 'PRI') {
                $primary_field = $field;
            }
            // Check for a UNIQUE key if no primary key
            elseif ($key == 'UNI' && !$auto_increment_found && !$unique_found) {
                $primary_field = $field;
                $unique_found = true;
            }
        }

        // If no primary or unique key is found, fallback to an MD5 hash of concatenated fields
        if ($primary_field == '') {
            $primary_field = "MD5(CONCAT_WS('', ";
            $fields = array_column($result1, 'Field');
            $primary_field .= "'" . implode("', '", $fields) . "'))";
        }

        return $primary_field;
    }
}
 
 
if (!function_exists('FormBuilder')) {
    function FormBuilder($database, $table, $title = '', $skip_positions = array(), $formid = 1, $primary_id = 0)
    {
        $db = DB::getInstance(); // Get the UserSpice database instance
        $primary_field = FindPrime($database, $table); // Find the primary field
        $field_values = []; // Array to hold existing field values if $primary_id > 0

        // If $primary_id > 0, fetch the existing values to populate the form
        if ($primary_id > 0) {
            $sql2 = "SELECT * FROM `$database`.`$table` WHERE `$primary_field` = ?";
            $db->query($sql2, [$primary_id]);
            $row_data = $db->first();
            if ($row_data) {
                foreach ($row_data as $key => $value) {
                    $field_values[$key] = $value;
                }
            }
        }

        // Start building the form
        $out = '<form class="form-horizontal" name="form'.$formid.'" id="mainForm'.$formid.'" method="post" enctype="multipart/form-data" action="">
                <fieldset>

                <!-- Form Name -->
                <legend>'.htmlspecialchars($title).'</legend>';

        // Get table structure
        $structure = array();
        $sql1 = "DESCRIBE `$database`.`$table`";
        $db->query($sql1);
        $result1 = $db->results();
        
        foreach ($result1 as $row) {
            $structure[] = (array) $row;
        }

        // Loop through structure and apply conditions based on Type
        $field_position = 0; // Initialize field position counter
        foreach ($structure as $field) {
            $field_position++; // Increment field position

            // Skip the field if its position is in the $skip_positions array
            if (in_array($field_position, $skip_positions)) {
                continue;
            }

            $fieldType = $field['Type'];
            $fieldName = htmlspecialchars($field['Field']); // Sanitized field name for HTML output
            $fieldLabel = ucwords(str_replace('_', ' ', $fieldName)); // Generate a label from the field name
            $fieldValue = isset($field_values[$fieldName]) ? htmlspecialchars($field_values[$fieldName]) : ''; // Fetch field value if it exists

            // Conditions for different field types
            if (strpos($fieldType, 'int') !== false) {
                // Handle integer types
                $out .= '<!-- Integer input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="number" value="'.$fieldValue.'" placeholder="Enter number" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'float') !== false || strpos($fieldType, 'double') !== false || strpos($fieldType, 'decimal') !== false) {
                // Handle float, double, decimal types
                $out .= '<!-- Float input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="text" value="'.$fieldValue.'" placeholder="Enter decimal number" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'bit') !== false) {
                // Handle bit type
                $out .= '<!-- Bit input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="checkbox" '.($fieldValue ? 'checked' : '').' class="form-check-input">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'bool') !== false || strpos($fieldType, 'boolean') !== false) {
                // Handle boolean types
                $out .= '<!-- Boolean input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="checkbox" '.($fieldValue ? 'checked' : '').' class="form-check-input">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'char') !== false || strpos($fieldType, 'varchar') !== false) {
                // Handle char and varchar types
                $out .= '<!-- Char/Varchar input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="text" value="'.$fieldValue.'" placeholder="Enter text" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'text') !== false) {
                // Handle text types
                $out .= '<!-- Text input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <textarea id="'.$fieldName.'" name="'.$fieldName.'" placeholder="Enter text" class="form-control">'.$fieldValue.'</textarea>
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'date') !== false) {
                // Handle date type
                $out .= '<!-- Date input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="date" value="'.$fieldValue.'" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'datetime') !== false || strpos($fieldType, 'timestamp') !== false) {
                // Handle datetime and timestamp types
                $out .= '<!-- DateTime input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="datetime-local" value="'.$fieldValue.'" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'time') !== false) {
                // Handle time type
                $out .= '<!-- Time input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="time" value="'.$fieldValue.'" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'year') !== false) {
                // Handle year type
                $out .= '<!-- Year input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <input id="'.$fieldName.'" name="'.$fieldName.'" type="number" min="1900" max="2099" step="1" value="'.$fieldValue.'" class="form-control input-md">
                        </div>
                        </div>';
            } elseif (strpos($fieldType, 'set') !== false) {
                // Handle SET type
                preg_match("/^set\((.+)\)$/i", $fieldType, $matches); // Extract values from the SET definition
                $options = [];
                if (!empty($matches[1])) {
                    $options = str_getcsv($matches[1], ',', "'");
                }

                $out .= '<!-- SET input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label" for="'.$fieldName.'">'.$fieldLabel.'</label>  
                        <div class="col-md-4">
                        <select id="'.$fieldName.'" name="'.$fieldName.'" class="form-control">';
                
                // Generate options for the SET select input
                foreach ($options as $option) {
                    $selected = ($fieldValue == $option) ? 'selected' : '';
                    $out .= '<option value="'.htmlspecialchars($option).'" '.$selected.'>'.htmlspecialchars($option).'</option>';
                }

                $out .= '   </select>
                        </div>
                        </div>';
            } else {
                // Handle other or unknown types
                $out .= '<!-- Unknown type input - '.$fieldType.' -->';
            }
        }

        $out .= '
        <!-- Submit Button -->
        <div class="form-group">
        <label class="col-md-4 control-label" for="submit"></label>
        <div class="col-md-4">
            <button id="submit" name="submit" class="btn btn-danger">Submit</button>
        </div>
        </div>

        </fieldset>
        </form>';

        return $out;
    }
}

