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
                'unsigned'   => true,
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
        $this->forge->addForeignKey('id_arrondissement', 'Arrondissement', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('Recensement');
    }

    public function down()
    {
        $this->forge->dropTable('Recensement');
    }
}
