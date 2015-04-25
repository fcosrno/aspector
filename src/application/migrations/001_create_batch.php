<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_batch extends CI_Migration {

	public function up()
    {
            $this->dbforge->add_field(array(
                    'key' => array(
                            'type' => 'VARCHAR',
                            'constraint' => 900,
                    ),
                    'status' => array(
                            'type' => 'VARCHAR',
                            'constraint' => '100',
                    ),
            ));
            $this->dbforge->add_key('key', TRUE);
            $this->dbforge->create_table('batch');
    }

    public function down()
    {
            $this->dbforge->drop_table('batch');
    }
}

