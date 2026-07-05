<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RouteSegments extends Migration
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
                'type' => 'INT',
                'null' => true,
            ],

            'arrondissement_nom' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'nom' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'type_route' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'surface' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'smoothness' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'largeur_m' => [
                'type' => 'NUMERIC',
                'null' => true,
            ],

            'nb_voies' => [
                'type' => 'INTEGER',
                'null' => true,
            ],

            'sens_unique' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'bridge' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'layer_value' => [
                'type' => 'INTEGER',
                'null' => true,
            ],

            'source' => [
                'type' => 'BIGINT',
                'null' => true,
            ],

            'target' => [
                'type' => 'BIGINT',
                'null' => true,
            ],

            'longueur_m' => [
                'type' => 'DOUBLE PRECISION',
                'null' => true,
            ],

            'cost' => [
                'type' => 'DOUBLE PRECISION',
                'null' => true,
            ],

            'reverse_cost' => [
                'type' => 'DOUBLE PRECISION',
                'null' => true,
            ],
        ]);

        $this->forge->addField('geom geometry(LineString, 4326)');

        $this->forge->addKey('id', true);

        $this->forge->addForeignKey('id_arrondissement', 'arrondissement', 'id', 'CASCADE', 'CASCADE');

        $this->forge->addKey('source');
        $this->forge->addKey('target');
        $this->forge->addKey('type_route');

        $this->forge->createTable('route_segments', true);

        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_route_segments_geom
            ON route_segments
            USING GIST (geom)
        ");
    }

    public function down()
    {
        $this->forge->dropTable('route_segments_vertices_pgr', true);

        $this->forge->dropTable('route_segments', true);
    }
}
