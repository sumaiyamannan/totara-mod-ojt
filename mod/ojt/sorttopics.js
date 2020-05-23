/*
 * Copyright (C) 2018 Kineo
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kaushtuv Gurung <kaushtuv.gurung@kineo.com.au>
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_ojt_sorttopics = M.mod_ojt_mod_ojt_sorttopics || {

    Y: null,

    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args) {
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }


        var config = this.config;

        // Init topic expand/collapse
        $( function() {
            // init sortable topic items
            $('.config-mod-ojt-topic-items').sortable();
            
            // init sortable topic
            $('.ojt-sortable-topics').sortable();
            
            // save item positions
            $('.btn-sort-topic-items').click(function(){
                M.mod_ojt_sorttopics.sort_topic_items($(this).attr('data-topicid'));
            });
            
            // save topic positions
            $('#save-sort-ojt-topics').click(function(){
                M.mod_ojt_sorttopics.sort_topics();
            });
            
            // show modal
            $('#update-ojt-topics-order').click(function() {
                M.mod_ojt_sorttopics.show_topic_sort_modal();
            })
            
            // hide modal
            $('#cancel-sort-ojt-topics').click(function() {
                M.mod_ojt_sorttopics.hide_topic_sort_modal();
            })
        });
        


    },  // init
    
    sort_topic_items: function(topicid) {
        var form = $('#ojt-topic-items-sort-form-'+topicid);
        M.mod_ojt_sorttopics.add_loading();
        $.ajax({
            method: "POST",
            url: M.cfg.wwwroot+"/mod/ojt/ajax/sort_topic_items.php",
            dataType: 'JSON',
            data:form.serialize()
        })
        .done(function(data) {
            if(data.success) {
                M.mod_ojt_sorttopics.remove_loading();
            }
        })
        .fail(function() {
            // do something
            M.mod_ojt_sorttopics.remove_loading();
        });
    },
    
    sort_topics: function() {
        var form = $('#ojt-topic-sort-form');
        M.mod_ojt_sorttopics.add_loading();
        $.ajax({
            method: "POST",
            url: M.cfg.wwwroot+"/mod/ojt/ajax/sort_topics.php",
            dataType: 'JSON',
            data:form.serialize()
        })
        .done(function(data) {
            if(data.success) {
                // refresh page
                location.reload();
            }
        })
        .fail(function() {
            // do something
            M.mod_ojt_sorttopics.remove_loading();
            M.mod_ojt_sorttopics.hide_topic_sort_modal();
        });
    },
    
    add_loading: function() {
        var loading = '<div class="ojt-sort-loading"></div>';
        $('body').append(loading);
    },
    
    remove_loading: function() {
        $('.ojt-sort-loading').remove();
    },
    
    show_topic_sort_modal: function() {
        $('.ojt-modal-overlay').show();
        $('#ojt-modal').show();
    },
    
    hide_topic_sort_modal: function() {
        $('.ojt-modal-overlay').hide();
        $('#ojt-modal').hide();
    }
}