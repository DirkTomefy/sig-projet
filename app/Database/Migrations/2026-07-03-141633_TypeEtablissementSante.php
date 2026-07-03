<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TypeEtablissementSante extends Migration
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
            'libelle' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'icone' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'couleur_carte' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('Type_Etablissement_Sante');
    }

    public function down()
    {
        $this->forge->dropTable('Type_Etablissement_Sante');
    }
}
