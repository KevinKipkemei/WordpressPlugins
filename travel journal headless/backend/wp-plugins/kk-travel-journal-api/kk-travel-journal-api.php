<?php
/**
 * Plugin Name: KK Headless Travel Journal API
 * Description: Custom post types and WPGraphQL schema integrations for the Headless Next.js Travel Journal.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register Custom Post Types
add_action( 'init', 'kk_register_travel_cpts' );
function kk_register_travel_cpts() {
    // Destinations CPT
    register_post_type( 'destination', [
        'labels' => [
            'name'               => _x( 'Destinations', 'post type general name', 'kk-travel-journal' ),
            'singular_name'      => _x( 'Destination', 'post type singular name', 'kk-travel-journal' ),
            'menu_name'          => _x( 'Destinations', 'admin menu', 'kk-travel-journal' ),
            'name_admin_bar'     => _x( 'Destination', 'add new on admin bar', 'kk-travel-journal' ),
            'add_new'            => _x( 'Add New', 'destination', 'kk-travel-journal' ),
            'add_new_item'       => __( 'Add New Destination', 'kk-travel-journal' ),
            'new_item'           => __( 'New Destination', 'kk-travel-journal' ),
            'edit_item'          => __( 'Edit Destination', 'kk-travel-journal' ),
            'view_item'          => __( 'View Destination', 'kk-travel-journal' ),
            'all_items'          => __( 'All Destinations', 'kk-travel-journal' ),
            'search_items'       => __( 'Search Destinations', 'kk-travel-journal' ),
            'not_found'          => __( 'No destinations found.', 'kk-travel-journal' ),
            'not_found_in_trash' => __( 'No destinations found in Trash.', 'kk-travel-journal' ),
        ],
        'public'             => true,
        'has_archive'        => true,
        'show_in_rest'       => true,
        'show_in_graphql'    => true,
        'graphql_single_name'=> 'destination',
        'graphql_plural_name'=> 'destinations',
        'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'menu_icon'          => 'dashicons-location-alt',
        'rewrite'            => [ 'slug' => 'destinations' ],
    ]);

    // Trips CPT
    register_post_type( 'trip', [
        'labels' => [
            'name'               => _x( 'Trips', 'post type general name', 'kk-travel-journal' ),
            'singular_name'      => _x( 'Trip', 'post type singular name', 'kk-travel-journal' ),
            'menu_name'          => _x( 'Trips', 'admin menu', 'kk-travel-journal' ),
            'name_admin_bar'     => _x( 'Trip', 'add new on admin bar', 'kk-travel-journal' ),
            'add_new'            => _x( 'Add New', 'trip', 'kk-travel-journal' ),
            'add_new_item'       => __( 'Add New Trip', 'kk-travel-journal' ),
            'new_item'           => __( 'New Trip', 'kk-travel-journal' ),
            'edit_item'          => __( 'Edit Trip', 'kk-travel-journal' ),
            'view_item'          => __( 'View Trip', 'kk-travel-journal' ),
            'all_items'          => __( 'All Trips', 'kk-travel-journal' ),
            'search_items'       => __( 'Search Trips', 'kk-travel-journal' ),
            'not_found'          => __( 'No trips found.', 'kk-travel-journal' ),
            'not_found_in_trash' => __( 'No trips found in Trash.', 'kk-travel-journal' ),
        ],
        'public'             => true,
        'has_archive'        => true,
        'show_in_rest'       => true,
        'show_in_graphql'    => true,
        'graphql_single_name'=> 'trip',
        'graphql_plural_name'=> 'trips',
        'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'menu_icon'          => 'dashicons-airplane',
        'rewrite'            => [ 'slug' => 'trips' ],
    ]);
}

// 2. Add Meta Boxes in WordPress Admin
add_action( 'add_meta_boxes', 'kk_travel_meta_boxes' );
function kk_travel_meta_boxes() {
    add_meta_box(
        'kk_destination_meta',
        __( 'Destination Details', 'kk-travel-journal' ),
        'kk_destination_meta_callback',
        'destination',
        'normal',
        'default'
    );

    add_meta_box(
        'kk_trip_meta',
        __( 'Trip Details', 'kk-travel-journal' ),
        'kk_trip_meta_callback',
        'trip',
        'normal',
        'default'
    );
}

function kk_destination_meta_callback( $post ) {
    wp_nonce_field( 'kk_destination_save_meta', 'kk_destination_meta_nonce' );
    $country = get_post_meta( $post->ID, 'country', true );
    $best_time = get_post_meta( $post->ID, 'best_time_to_visit', true );
    ?>
    <p>
        <label for="kk_country"><strong>Country:</strong></label><br>
        <input type="text" id="kk_country" name="kk_country" value="<?php echo esc_attr( $country ); ?>" style="width:100%; max-width:400px;" />
    </p>
    <p>
        <label for="kk_best_time"><strong>Best Time to Visit:</strong></label><br>
        <input type="text" id="kk_best_time" name="kk_best_time" value="<?php echo esc_attr( $best_time ); ?>" style="width:100%; max-width:400px;" placeholder="e.g., June - Sept" />
    </p>
    <?php
}

function kk_trip_meta_callback( $post ) {
    wp_nonce_field( 'kk_trip_save_meta', 'kk_trip_meta_nonce' );
    $travel_date = get_post_meta( $post->ID, 'travel_date', true );
    $duration = get_post_meta( $post->ID, 'duration_days', true );
    $cost = get_post_meta( $post->ID, 'cost_usd', true );
    $difficulty = get_post_meta( $post->ID, 'difficulty', true );
    $destination_id = get_post_meta( $post->ID, 'destination_id', true );

    // Get all destinations for the dropdown
    $destinations = get_posts([
        'post_type'      => 'destination',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);
    ?>
    <p>
        <label for="kk_travel_date"><strong>Travel Date:</strong></label><br>
        <input type="date" id="kk_travel_date" name="kk_travel_date" value="<?php echo esc_attr( $travel_date ); ?>" style="width:100%; max-width:200px;" />
    </p>
    <p>
        <label for="kk_duration"><strong>Duration (Days):</strong></label><br>
        <input type="number" id="kk_duration" name="kk_duration" value="<?php echo esc_attr( $duration ); ?>" min="1" style="width:100%; max-width:200px;" />
    </p>
    <p>
        <label for="kk_cost"><strong>Cost (USD):</strong></label><br>
        <input type="number" id="kk_cost" name="kk_cost" value="<?php echo esc_attr( $cost ); ?>" min="0" style="width:100%; max-width:200px;" />
    </p>
    <p>
        <label for="kk_difficulty"><strong>Difficulty Level:</strong></label><br>
        <select id="kk_difficulty" name="kk_difficulty" style="width:100%; max-width:200px;">
            <option value="Easy" <?php selected( $difficulty, 'Easy' ); ?>>Easy</option>
            <option value="Moderate" <?php selected( $difficulty, 'Moderate' ); ?>>Moderate</option>
            <option value="Challenging" <?php selected( $difficulty, 'Challenging' ); ?>>Challenging</option>
        </select>
    </p>
    <p>
        <label for="kk_destination_id"><strong>Linked Destination:</strong></label><br>
        <select id="kk_destination_id" name="kk_destination_id" style="width:100%; max-width:300px;">
            <option value=""><?php _e( '-- Select Destination --', 'kk-travel-journal' ); ?></option>
            <?php foreach ( $destinations as $dest ) : ?>
                <option value="<?php echo esc_attr( $dest->ID ); ?>" <?php selected( $destination_id, $dest->ID ); ?>>
                    <?php echo esc_html( $dest->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

// 3. Save Meta Box Values
add_action( 'save_post', 'kk_save_travel_meta' );
function kk_save_travel_meta( $post_id ) {
    // Save Destination Meta
    if ( isset( $_POST['kk_destination_meta_nonce'] ) && wp_verify_nonce( $_POST['kk_destination_meta_nonce'], 'kk_destination_save_meta' ) ) {
        if ( isset( $_POST['kk_country'] ) ) {
            update_post_meta( $post_id, 'country', sanitize_text_field( $_POST['kk_country'] ) );
        }
        if ( isset( $_POST['kk_best_time'] ) ) {
            update_post_meta( $post_id, 'best_time_to_visit', sanitize_text_field( $_POST['kk_best_time'] ) );
        }
    }

    // Save Trip Meta
    if ( isset( $_POST['kk_trip_meta_nonce'] ) && wp_verify_nonce( $_POST['kk_trip_meta_nonce'], 'kk_trip_save_meta' ) ) {
        if ( isset( $_POST['kk_travel_date'] ) ) {
            update_post_meta( $post_id, 'travel_date', sanitize_text_field( $_POST['kk_travel_date'] ) );
        }
        if ( isset( $_POST['kk_duration'] ) ) {
            update_post_meta( $post_id, 'duration_days', intval( $_POST['kk_duration'] ) );
        }
        if ( isset( $_POST['kk_cost'] ) ) {
            update_post_meta( $post_id, 'cost_usd', intval( $_POST['kk_cost'] ) );
        }
        if ( isset( $_POST['kk_difficulty'] ) ) {
            update_post_meta( $post_id, 'difficulty', sanitize_text_field( $_POST['kk_difficulty'] ) );
        }
        if ( isset( $_POST['kk_destination_id'] ) ) {
            update_post_meta( $post_id, 'destination_id', intval( $_POST['kk_destination_id'] ) );
        }
    }
}

// 4. Register Custom Fields to WPGraphQL Schema
add_action( 'graphql_register_types', 'kk_register_graphql_travel_fields' );
function kk_register_graphql_travel_fields() {
    
    // Helper function to extract database ID robustly from both model and object structures
    $get_db_id = function( $post ) {
        if ( is_object( $post ) ) {
            if ( isset( $post->databaseId ) ) {
                return $post->databaseId;
            }
            if ( isset( $post->ID ) ) {
                return $post->ID;
            }
        }
        if ( is_array( $post ) ) {
            return isset( $post['databaseId'] ) ? $post['databaseId'] : ( isset( $post['ID'] ) ? $post['ID'] : null );
        }
        return null;
    };

    // Destination - country
    register_graphql_field( 'Destination', 'country', [
        'type'        => 'String',
        'description' => __( 'The country of the destination', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            return $id ? get_post_meta( $id, 'country', true ) : null;
        }
    ]);

    // Destination - bestTimeToVisit
    register_graphql_field( 'Destination', 'bestTimeToVisit', [
        'type'        => 'String',
        'description' => __( 'Best time to visit this destination', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            return $id ? get_post_meta( $id, 'best_time_to_visit', true ) : null;
        }
    ]);

    // Trip - travelDate
    register_graphql_field( 'Trip', 'travelDate', [
        'type'        => 'String',
        'description' => __( 'The date of travel', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            return $id ? get_post_meta( $id, 'travel_date', true ) : null;
        }
    ]);

    // Trip - durationDays
    register_graphql_field( 'Trip', 'durationDays', [
        'type'        => 'Int',
        'description' => __( 'Trip duration in days', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            $val = $id ? get_post_meta( $id, 'duration_days', true ) : null;
            return ! is_null( $val ) && $val !== '' ? intval( $val ) : null;
        }
    ]);

    // Trip - costUsd
    register_graphql_field( 'Trip', 'costUsd', [
        'type'        => 'Int',
        'description' => __( 'Estimated cost of the trip in USD', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            $val = $id ? get_post_meta( $id, 'cost_usd', true ) : null;
            return ! is_null( $val ) && $val !== '' ? intval( $val ) : null;
        }
    ]);

    // Trip - difficulty
    register_graphql_field( 'Trip', 'difficulty', [
        'type'        => 'String',
        'description' => __( 'Difficulty level (Easy, Moderate, Challenging)', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            return $id ? get_post_meta( $id, 'difficulty', true ) : null;
        }
    ]);

    // Trip -> Destination Relationship
    register_graphql_field( 'Trip', 'destination', [
        'type'        => 'Destination',
        'description' => __( 'The destination linked to this trip', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            if ( ! $id ) {
                return null;
            }
            $dest_id = get_post_meta( $id, 'destination_id', true );
            if ( $dest_id ) {
                $dest_post = get_post( $dest_id );
                if ( $dest_post ) {
                    return new \WPGraphQL\Model\Post( $dest_post );
                }
            }
            return null;
        }
    ]);

    // Destination -> Trips Connection (Reverse Relationship)
    register_graphql_field( 'Destination', 'trips', [
        'type'        => [ 'list_of' => 'Trip' ],
        'description' => __( 'All trips taken to this destination', 'kk-travel-journal' ),
        'resolve'     => function( $post ) use ( $get_db_id ) {
            $id = $get_db_id( $post );
            if ( ! $id ) {
                return [];
            }
            $query = new \WP_Query([
                'post_type'      => 'trip',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => [
                    [
                        'key'     => 'destination_id',
                        'value'   => $id,
                        'compare' => '='
                    ]
                ]
            ]);
            $trips = [];
            foreach ( $query->posts as $p ) {
                $trips[] = new \WPGraphQL\Model\Post( $p );
            }
            return $trips;
        }
    ]);
}

// 5. Automatic Mock Data Seeder on Activation
register_activation_hook( __FILE__, 'kk_travel_journal_seed_data' );
function kk_travel_journal_seed_data() {
    // Register types dynamically so we can insert them right away if needed
    kk_register_travel_cpts();
    flush_rewrite_rules();

    // Check if we already seeded destinations to avoid duplication
    $existing = get_posts([
        'post_type'      => 'destination',
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);

    if ( ! empty( $existing ) ) {
        return; // Already seeded or posts exist
    }

    // Seed mock data
    $mock_data = [
        [
            'title'             => 'Kyoto',
            'content'           => 'Kyoto, once the capital of Japan, is a city famous for its thousands of classical Buddhist temples, gardens, imperial palaces, Shinto shrines and traditional wooden houses.',
            'country'           => 'Japan',
            'best_time_to_visit'=> 'October - November',
            'trips'             => [
                [
                    'title'       => 'Autumn Colors & Ancient Temples',
                    'content'     => 'An unforgettable walk through the vibrant red maple leaves in Tofuku-ji, drinking matcha in Kiyomizu-dera, and wandering through the thousands of vermilion torii gates at Fushimi Inari Shrine at dusk.',
                    'travel_date' => '2025-11-10',
                    'duration'    => 5,
                    'cost'        => 1200,
                    'difficulty'  => 'Easy',
                ],
                [
                    'title'       => 'Cycling Through Bamboo Groves',
                    'content'     => 'Rented a city bike early in the morning to beat the crowds in Arashiyama. We rode through the towering bamboo groves and up the hills to the monkey park, catching beautiful views of the Katsura River.',
                    'travel_date' => '2026-04-12',
                    'duration'    => 3,
                    'cost'        => 450,
                    'difficulty'  => 'Moderate',
                ]
            ]
        ],
        [
            'title'             => 'Patagonia',
            'content'           => 'Patagonia is a sparse, spectacular region at the southern end of South America, shared by Argentina and Chile. It features dramatic mountain peaks, massive glaciers, and windswept pampas.',
            'country'           => 'Chile',
            'best_time_to_visit'=> 'December - February',
            'trips'             => [
                [
                    'title'       => 'Trekking the W-Trek Solo',
                    'content'     => 'Hiked the iconic W-Trek in Torres del Paine National Park. Camped under starry skies, stood at the base of the massive granite towers at sunrise, and battled strong Patagonian winds to reach French Valley.',
                    'travel_date' => '2025-01-15',
                    'duration'    => 7,
                    'cost'        => 800,
                    'difficulty'  => 'Challenging',
                ]
            ]
        ],
        [
            'title'             => 'Swiss Alps',
            'content'           => 'The Swiss Alps form a massive part of the Alpine range. Known for iconic peaks like the Matterhorn and Eiger, Swiss Alpine valleys offer breathtaking meadows and crystal-clear lakes.',
            'country'           => 'Switzerland',
            'best_time_to_visit'=> 'June - August',
            'trips'             => [
                [
                    'title'       => 'Conquering the Tour du Mont Blanc',
                    'content'     => 'A high-altitude hiking adventure covering over 170 kilometers of Alpine trails. We climbed steep mountain passes, ate freshly made cheese at high pastures, and spent nights in cozy mountain refuges.',
                    'travel_date' => '2025-07-20',
                    'duration'    => 10,
                    'cost'        => 1800,
                    'difficulty'  => 'Challenging',
                ]
            ]
        ],
        [
            'title'             => 'Serengeti National Park',
            'content'           => 'The Serengeti is a vast ecosystem in east-central Africa. It is famous for its massive annual migration of millions of wildebeest, zebras, and gazelles, alongside thriving predator populations.',
            'country'           => 'Tanzania',
            'best_time_to_visit'=> 'June - October',
            'trips'             => [
                [
                    'title'       => 'Serengeti Migration Safari',
                    'content'     => 'Woke up at 5:00 AM to catch a hot air balloon over the Serengeti plains. We witnessed thousands of wildebeest crossing the Mara River and spotted lions, leopards, and elephants hunting in the golden tall grass.',
                    'travel_date' => '2025-08-05',
                    'duration'    => 4,
                    'cost'        => 2500,
                    'difficulty'  => 'Easy',
                ]
            ]
        ]
    ];

    foreach ( $mock_data as $dest_info ) {
        // Create Destination
        $dest_id = wp_insert_post([
            'post_title'   => $dest_info['title'],
            'post_content' => $dest_info['content'],
            'post_status'  => 'publish',
            'post_type'    => 'destination',
        ]);

        if ( ! is_wp_error( $dest_id ) ) {
            update_post_meta( $dest_id, 'country', $dest_info['country'] );
            update_post_meta( $dest_id, 'best_time_to_visit', $dest_info['best_time_to_visit'] );

            // Create Trips for this destination
            foreach ( $dest_info['trips'] as $trip_info ) {
                $trip_id = wp_insert_post([
                    'post_title'   => $trip_info['title'],
                    'post_content' => $trip_info['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'trip',
                ]);

                if ( ! is_wp_error( $trip_id ) ) {
                    update_post_meta( $trip_id, 'travel_date', $trip_info['travel_date'] );
                    update_post_meta( $trip_id, 'duration_days', $trip_info['duration'] );
                    update_post_meta( $trip_id, 'cost_usd', $trip_info['cost'] );
                    update_post_meta( $trip_id, 'difficulty', $trip_info['difficulty'] );
                    update_post_meta( $trip_id, 'destination_id', $dest_id );
                }
            }
        }
    }
}
