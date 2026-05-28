<?php

namespace WpPublicRevisions\Core;

use WP_Error;
use WpPublicRevisions\Lib\Diff;

class RevisionStore {
    private $wpdb;
    private string $table_name;
    private int $post_id;

    function __construct( int $post_id ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix ."wppr_revisions";
        $this->post_id = $post_id;
    }

    /**
     * Posts the revision into the database compressing the content
     * @param string $content
     * @param string $label
     * @return int | WP_Error Returns the ID of the revision or an error
     */
    public function post_revision( string $content, string $label = "" ) : int | WP_Error {
        if ( ! current_user_can("edit_post", $this->post_id ) ) return new WP_Error("forbidden", __("This user cannot post a revision", "wp-public-revisions") );

        // Make sure that the content is different
        $res = $this->wpdb->get_var( $this->wpdb->prepare("SELECT content FROM $this->table_name WHERE post_id = %d ORDER BY rev_no DESC LIMIT 1", $this->post_id ) );

        if ( $res ) {
            $current_content = Diff::apply_diff($res);
            if ( $content === $current_content ) {
                return new WP_Error("same_content", __("The content is the same. Nothing to update.","wp-public-revisions") );
            }
        }

        // Post the revision
        $revision_counts = $this->wpdb->get_var( $this->wpdb->prepare("SELECT MAX(rev_no) FROM $this->table_name WHERE post_id=%d", $this->post_id ) );
        $compressed_content = Diff::generate_diff($content);
        
        $args = array(
            "post_id"=> $this->post_id,
            "author" => get_current_user_id(),
            "rev_no" => $revision_counts + 1,
            "label" => $label,
            "content" => $compressed_content,
        );

        $res = $this->wpdb->insert( $this->table_name, $args );
        
        if ( ! $res ) {
            return new WP_Error("insert_error", __("Insert error","wp-public-revisions") );
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Fetches the revision
     * @param int $rev_id
     * @return Object | WP_Error
     */
    public function fetch_revision( int $rev_id ) : Object {
        $res = $this->wpdb->get_row( $this->wpdb->prepare("SELECT * FROM $this->table_name WHERE id=%d", $rev_id ) );

        if ( ! $res ) {
            return new WP_Error("not_found", __("Post was not found!","wp-public-revisions") );
        }

        $res->content = $this->read_revision( $res->content );

        return $res;
    }

    /**
     * Returns the string of the revision that is compressed
     * @param string $content in bytes
     * @return string | WP_Error
     */
    private function read_revision(string $content) : string | WP_Error {
        return Diff::apply_diff($content);
    }

    /**
     * Fetches all the revisions for the admin-side.
     * @return array | WP_Error
     */
    public function fetch_revisions() : array | WP_Error {
        $res = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT id, label, author, rev_no, post_id, timestamp FROM $this->table_name WHERE post_id = %d ORDER BY rev_no DESC", $this->post_id )
        );

        if ( $this->wpdb->last_error ) {
            return new WP_Error( "sql_error", $this->wpdb->last_error );
        }

        return $res;
    }
    
    /**
     * Delets a specific revision
     * @param int $rev_id
     * @return bool | WP_Error
     */
    public function delete_revision(int $rev_id) : bool | WP_Error {
        $res = $this->wpdb->delete($this->table_name, [
            "post_id"=> $this->post_id,
            "id"=> $rev_id,
        ], [
            "%d",
            "%d"
        ]);

        if ( false === $res ) {
            return new WP_Error("delete_error", __("Unable to delete the revision","wp-public-revisions") );
        } else if ( 0 === $res ) {
            return new WP_Error("no-deletion", __("No rows were affected","wp-public-revisions") );
        }

        return true;
    }

    /**
     * Deletes all the revisions of a post
     * @return bool | WP_Error
     */
    public function delete_revisions() : bool | WP_Error {
        $result = $this->wpdb->delete($this->table_name, [
            "post_id"=> $this->post_id
        ], [
            "%d"
        ]);

        if ( ! $result ) {  
            return new WP_Error("no-deletion", __("No rows were affected","wp-public-revisions") );
        }

        return true;
    }
}