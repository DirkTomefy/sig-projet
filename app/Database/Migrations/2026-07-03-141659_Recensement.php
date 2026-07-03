<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Recensement extends Migration
{
  public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_arrondissement' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => false,
            ],
            'annee' => [
                'type'       => 'INT',
                'constraint' => 4,
            ],
            'population' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'menages' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'date_recensement' => [
                'type' => 'DATE',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        
        // Définition de la Clé Étrangère
        $this->forge->addForeignKey('id_arrondissement', 'arrondissement', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('recensement');
    }

    public function down()
    {
        $this->forge->dropTable('recensement');
    }
}
