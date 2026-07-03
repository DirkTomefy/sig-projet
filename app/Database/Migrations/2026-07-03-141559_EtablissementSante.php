<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EtablissementSante extends Migration
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
            'nom' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'id_type' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'adresse' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'contact' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
            ],
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
            ],
            'geom' => [
                'type' => 'GEOMETRY',
            ],
        ]);

        $this->forge->addKey('id', true);
        
        // Définition de la Clé Étrangère vers la table des types
        $this->forge->addForeignKey('id_type', 'Type_Etablissement_Sante', 'UniqueID', 'SET NULL', 'CASCADE');
        
        $this->forge->createTable('Etablissement_sante');

        // Optimisation PostGIS : Enforce type POINT et SRID WGS84 (4326) + Index Spatial
        $this->db->query("ALTER TABLE Etablissement_sante ALTER COLUMN geom TYPE geometry(Point, 4326) USING ST_SetSRID(geom, 4326);");
        $this->db->query("CREATE INDEX idx_etablissement_geom ON Etablissement_sante USING GIST (geom);");
    }

    public function down()
    {
        $this->forge->dropTable('Etablissement_sante');
    }
}