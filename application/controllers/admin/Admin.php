<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('rbac');
        $this->load->library('csvimport');
		$this->load->model('admin/admin_model', 'admin');

		$this->rbac->check_module_access();
    }

	//-----------------------------------------------------		
	function index($type='')
	{
		$this->session->set_userdata('filter_type',$type);
		$this->session->set_userdata('filter_keyword','');
		$this->session->set_userdata('filter_status','');
		
		$data['admin_roles'] = $this->admin->get_admin_roles();
		$data['view']='admin/admin/index';
		$this->load->view('layout',$data);
	}

	//---------------------------------------------------------
	function filterdata()
	{
		$this->session->set_userdata('filter_type',$this->input->post('type'));
		$this->session->set_userdata('filter_status',$this->input->post('status'));
		$this->session->set_userdata('filter_keyword',$this->input->post('keyword'));
	}

	//--------------------------------------------------		
	function list_data()
	{
		$data['info'] = $this->admin->get_all();
		$this->load->view('admin/admin/list',$data);
	}

	//-----------------------------------------------------------
	function change_status()
	{   
		$this->rbac->check_operation_access(); // check opration permission

		$this->admin->change_status();
	}
	
	//--------------------------------------------------
	function add()
	{	
		$this->rbac->check_operation_access(); // check opration permission

		$data['admin_roles']=$this->admin->get_admin_roles();

		if($this->input->post('submit')){
				$this->form_validation->set_rules('username', 'Username', 'trim|required');
				$this->form_validation->set_rules('firstname', 'Firstname', 'trim|required');
				$this->form_validation->set_rules('lastname', 'Lastname', 'trim|required');
				$this->form_validation->set_rules('email', 'Email', 'trim|valid_email|required');
				$this->form_validation->set_rules('mobile_no', 'Number', 'trim|required');
				$this->form_validation->set_rules('password', 'Password', 'trim|required');
				$this->form_validation->set_rules('role', 'Role', 'trim|required');

				if ($this->form_validation->run() == FALSE) {
					$data['view'] = 'admin/admin/add';
					$this->load->view('layout', $data);
				}
				else{
					$data = array(
						'admin_role_id' => $this->input->post('role'),
						'username' => $this->input->post('username'),
						'firstname' => $this->input->post('firstname'),
						'lastname' => $this->input->post('lastname'),
						'email' => $this->input->post('email'),
						'mobile_no' => $this->input->post('mobile_no'),
						'password' =>  password_hash($this->input->post('password'), PASSWORD_BCRYPT),
						'is_active' => 1,
						'created_at' => date('Y-m-d : h:m:s'),
						'updated_at' => date('Y-m-d : h:m:s'),
					);
					$data = $this->security->xss_clean($data);
					$result = $this->admin->add_admin($data);
					if($result){
						$this->session->set_flashdata('msg', 'Admin has been added successfully!');
						redirect(base_url('admin/admin'));
					}
				}
			}
			else
			{
				$data['view']='admin/admin/add';
				$this->load->view('layout',$data);	
			}
	}

	//--------------------------------------------------
	function edit($id="")
	{
		$this->rbac->check_operation_access(); // check opration permission

		$data['admin_roles'] = $this->admin->get_admin_roles();

		if($this->input->post('submit')){
			$this->form_validation->set_rules('username', 'Username', 'trim|required');
			$this->form_validation->set_rules('firstname', 'Firstname', 'trim|required');
			$this->form_validation->set_rules('lastname', 'Lastname', 'trim|required');
			$this->form_validation->set_rules('email', 'Email', 'trim|valid_email|required');
			$this->form_validation->set_rules('mobile_no', 'Number', 'trim|required');
			$this->form_validation->set_rules('role', 'Role', 'trim|required');

			if ($this->form_validation->run() == FALSE) {
				$data['admin'] = $this->admin->get_admin_by_id($id);
				$data['view'] = 'admin/admin/edit';
				$this->load->view('layout', $data);
			}
			else{
				$data = array(
					'admin_role_id' => $this->input->post('role'),
					'username' => $this->input->post('username'),
					'firstname' => $this->input->post('firstname'),
					'lastname' => $this->input->post('lastname'),
					'email' => $this->input->post('email'),
					'mobile_no' => $this->input->post('mobile_no'),
					'is_active' => 1,
					'updated_at' => date('Y-m-d : h:m:s'),
				);

				$data = $this->security->xss_clean($data);
				//$result = $this->admin->edit_admin($data, $id);
				$result = true;

				if($result){
					$this->session->set_flashdata('msg', 'Edit Record has been Disabled in Demo!');
					redirect(base_url('admin/admin'));
				}
			}
		}
		elseif($id==""){
			redirect('admin/admin');
		}
		else{
			$data['admin'] = $this->admin->get_admin_by_id($id);
			$data['view'] = 'admin/admin/edit';
			$this->load->view('layout',$data);
		}		
	}

	//--------------------------------------------------
	function check_username($id=0)
    {
		$this->db->from('admin');
		$this->db->where('username', $this->input->post('username'));
		$this->db->where('admin_id !='.$id);
		$query=$this->db->get();
		if($query->num_rows() >0)
			echo 'false';
		else 
	    	echo 'true';
    }

    //------------------------------------------------------------
	function delete($id='')
	{   
		$this->rbac->check_operation_access(); // check opration permission

		//$this->admin->delete($id);
		$this->session->set_flashdata('msg','Delete operation has been disabled in demo.');	
		redirect('admin/admin');
	}


	public function importCsv()
	{
	
		//return array
		$return = array();
		
		
		$config['upload_path'] = './tmp/';
		$config['allowed_types'] = 'csv';

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload('thefile')) {
		
			//ouch, something's wrong!
			
			$this->data['error_message_heading'] = "Ouch!";
			$this->data['error_message'] = "Something went wrong with the file you were trying to upload: <br>".$this->upload->display_errors();
			
			$return['response_code'] = 2;
			
			$return['message'] = $this->load->view('partials/message_error', $this->data, true);
			
			die(json_encode($return));
			
			
		} else {
		
			//grab the file data
			$fileData = $this->upload->data();
			
			$this->load->library('csvreader');
			
			$suggestedDilimiter = $this->csvreader->guessDelimiter(base_url().'tmp/'.$fileData['file_name']);
			
			
			if( isset($_POST['separateColumns']) && $_POST['separateColumns'] != '' ) {
			
				//is the requested dilimiter different from the sniffed one?
				
				if( $suggestedDilimiter != $_POST['separateColumns'] ) {
				
					$this->data['error_message_heading'] = "Ouch!";
					$this->data['error_message'] = "It appears the value you've choses for the <b>Columns separated by</b> field does not match the delimiter detected by <b>Databased</b>. To prevent errors, we can't create the table. We suggest to leave the <b>Columns separated by</b> field empty and try again.";
					
					$return['response_code'] = 2;
					
					$return['message'] = $this->load->view('partials/message_error', $this->data, true);
					
					die(json_encode($return));
				
				} else {
			
					$dilimiter = $_POST['separateColumns'];
				
				}
			
			} else {
			
				$dilimiter = $suggestedDilimiter;
			
			}
			
			
			//set the enclosure
			if( isset($_POST['encloseColumns']) && $_POST['encloseColumns'] != '' ) {
			
				$enclosure = $_POST['encloseColumns'];
			
			} else {
			
				$enclosure = '"';
			
			}			
			
			$res = $this->tablemodel->importCSV($_POST['tableName'], $fileData, $dilimiter, $enclosure);
			
			//remove file
			unlink($fileData['full_path']);
			
			if( $res ==  true ) {
				
				//success message
				$this->data['success_message_heading'] = "Hooray!";
				$this->data['success_message'] = "Congrats! Your data was imported successfully! Click the button below to reload the page<br><a href='".site_url('db/'.$_POST['tableName'])."' class='btn btn-info btn-embossed'>reload page</a>";				
				
				$return['response_code'] = 1;
				
				$return['message'] = $this->load->view('partials/message_success', $this->data, true);
				
				die(json_encode($return));
			
			} elseif( $res == false ) {
				
				//error message
				$this->data['error_message_heading'] = "Ouch!";
				$this->data['error_message'] = "We can not import your data right now. This is most likely due to your CSV file being wrongly formatted (the number of fields might not match the numnber of columns in your table) or it could be that the primary key field contains values already present in your Databased table. Please review your CSV file and try again.";
				
				$return['response_code'] = 2;
				
				$return['message'] = $this->load->view('partials/message_error', $this->data, true);
				
				die(json_encode($return));
			
			}
	
		}
	
	}


	

	public function import1(){
        $data = array();
        $memData = array();
        
        // If import request is submitted
        if($this->input->post('importSubmit')){
            // Form field validation rules
            $this->form_validation->set_rules('file', 'CSV file', 'callback_file_check');
            
            // Validate submitted form data
            if($this->form_validation->run() == true){
                $insertCount = $updateCount = $rowCount = $notAddCount = 0;
                
                // If file uploaded
                if(is_uploaded_file($_FILES['file']['tmp_name'])){
                    // Load CSV reader library
                    $this->load->library('CSVReader');
                    
                    // Parse data from CSV file
                    $csvData = $this->csvreader->parse_csv($_FILES['file']['tmp_name']);
                    
                    // Insert/update CSV data into database
                    if(!empty($csvData)){
                        foreach($csvData as $row){ $rowCount++;
                            
                            // Prepare data for DB insertion
                            $memData = array(
                                'title_id' => $row['title_id'],
                                'question_name' => $row['question'],
                                'feild_type1' => $row['field1'],
                                'value1' => $row['value1'],
                                'feild_type2' => $row['field2'],
                                'value2' => $row['value2'],
                                'feild_type3' => $row['field3'],
                                'value3' => $row['value3'],
                                'feild_type4' => $row['field4'],
                                'value4' => $row['value4'],
                                'feild_type5' => $row['field5'],
                                'value5' => $row['value5'],
                                'datetime' => date('Y-m-d H:i:s'),
                            );
                            
                            // Check whether email already exists in the database
                            $con = array(
                                'where' => array(
                                    'email' => $row['Email']
                                ),
                                'returnType' => 'count'
                            );
                            // $prevCount = $this->admin->getRows($con);
                            print_r($memData);die;
                            // if($prevCount > 0){
                            //     // Update member data
                            //     $condition = array('email' => $row['Email']);
        	
                            //     $update = $this->member->update($memData, $condition);
                                
                            //     if($update){
                            //         $updateCount++;
                            //     }
                            // }else{
                                // Insert member data
                                $insert = $this->member->insert($memData);
                                
                            //     if($insert){
                            //         $insertCount++;
                            //     }
                            // }
                        }
                        
                        // Status message with imported data count
                        $notAddCount = ($rowCount - ($insertCount + $updateCount));
                        $successMsg = 'Members imported successfully. Total Rows ('.$rowCount.') | Inserted ('.$insertCount.') | Updated ('.$updateCount.') | Not Inserted ('.$notAddCount.')';
                        $this->session->set_userdata('success_msg', $successMsg);
                    }
                }else{
                    $this->session->set_userdata('error_msg', 'Error on file upload, please try again.');
                }
            }else{
                $this->session->set_userdata('error_msg', 'Invalid file, please select only CSV file.');
            }
        }
        else
        {
        	redirect('members');
        }
        
    }


    public function import12() {

            $path = FCPATH . "uploads/";

            $config['upload_path'] = $path;
            $config['allowed_types'] = 'csv';
            $config['max_size'] = 1024000;
            $this->load->library('upload', $config);

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('file')) {
                $error = $this->upload->display_errors();

                $this->session->set_flashdata('error', $this->upload->display_errors());
                redirect("users");
                echo $error['error'];die;
            } else {

                $file_data = $this->upload->data();
                $file_path = base_url() . "uploads/" . $file_data['file_name'];
                $this->load->library('CSVReader');
                $csv_data = $this->csvreader->parse_csv($file_path);

              // Add created and modified date if not include
                $date = date("Y-m-d H:i:s");
                print_r($csv_data);die;
                if ($csv_data) {
                    
                    foreach ($csv_data as $row) {

                        $insert_data[] = array(
                            'title_id' => $row['title_id'],
                                'question_name' => $row['question'],
                                'feild_type1' => $row['field1'],
                                'value1' => $row['value1'],
                                'feild_type2' => $row['field2'],
                                'value2' => $row['value2'],
                                'feild_type3' => $row['field3'],
                                'value3' => $row['value3'],
                                'feild_type4' => $row['field4'],
                                'value4' => $row['value4'],
                                'feild_type5' => $row['field5'],
                                'value5' => $row['value5'],
                                'datetime' => date('Y-m-d H:i:s'),
                        );
                
                    }
                   
              $this->member->insert($insert_data);
              $this->session->set_flashdata('success', "Csv imported successfully");
                    // redirect("users");

                } else {
                    $data['error'] = "Error occured";
                    $this->session->set_flashdata('error', $data['error']);
                    redirect("users");
                }
                
            }
        } 
	
	public function import_addon_prices()
	{
	    $createdon = date('Y-m-d H:i:s');   
	    $data['error'] = '';
	    $config['upload_path'] = './uploads/';
	    $config['allowed_types'] = 'csv';
	    $config['max_size'] = '1000';
	    $this->load->library('upload', $config);
	    if (!$this->upload->do_upload('file')) {
	        $data['error'] = $this->upload->display_errors();
	        print_r($data);die;
	    }else {
	        $file_data = $this->upload->data();
	        $file_path =  './uploads/'.$file_data['file_name'];
	        if ($this->csvimport->get_array($file_path)) {
	            $csv_array = $this->csvimport->get_array($file_path);
	            // print_r($csv_array);die;
	            foreach ($csv_array as $row) {
	                $insert_data = array(
                            'title_id' => $row['title_id'],
                                'question_name' => $row['question'],
                                'feild_type1' => $row['field1'],
                                'value1' => $row['value1'],
                                'feild_type2' => $row['field2'],
                                'value2' => $row['value2'],
                                'feild_type3' => $row['field3'],
                                'value3' => $row['value3'],
                                'feild_type4' => $row['field4'],
                                'value4' => $row['value4'],
                                'feild_type5' => $row['field5'],
                                'value5' => $row['value5'],
                                'datetime' => date('Y-m-d H:i:s'),
                       
	                );
	                $this->admin->insert_questions_csv($insert_data);
	            }
	            echo "Success";
	        } else{
	            echo "error";
	        }             
	    }
	}

	public function getdata()
	{
		define('UPLOAD_DIR', 'uploads/');
		 $data = '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';
	    // $image_parts = explode(";base64,", $img);
	    // $image_type_aux = explode("image/", $image_parts[0]);
	    // $image_type = $image_type_aux[1];
	    $image_base64 = base64_decode($data);
	    $file = UPLOAD_DIR . uniqid() . '.png';
	    fopen($file, 'w');
	    // file_put_contents($file, $image_base64);
	    // file_put_contents($file, $image_base64);
	}

	
}

?>