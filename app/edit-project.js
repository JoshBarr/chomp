module.exports =  {
    originalFormElementId: "form_groups",
    containerSelector: '[data-group-container]',
    groupSelector: "[data-sortable-group]",
    addSelector: "[data-add-group]",
    deleteSelector: "[data-delete-group]",
    formSelector: "form[name='form']",
    _divs: [],
    _sortables: [],
    groupTemplate: {
        "title": "Work block title...",
        "description": "Work block description...",
        "order": 0,
        "date": +new Date(),
        "id": 0
    },
	init: function() {
        this.formEl = document.querySelector(this.formSelector);
        // console.log(this.formEl);
        this.formElement = document.getElementById(this.originalFormElementId);
		var json = JSON.parse(this.formElement.value);

        this.addGroupBtn = document.querySelector(this.addSelector);
        this.deleteGroupBtn = document.querySelectorAll(this.deleteSelector);
        this.container = document.querySelector(this.containerSelector);
        this.addGroupBtn.addEventListener("click", this, false);
        this.container.addEventListener("click", this, false);
        this.formEl.addEventListener("submit", this, false);

        this.render(json);
	},

    serialize: function() {
        var groups = document.querySelectorAll(this.groupSelector);
        var group, items, item, itemData, groupData, data, len;

        data = [];
        len = groups.length;

        for (var i = 0; i < len; i++) {
            group = groups[i];
            groupId = group.dataset.sortableGroup;

            items = group.querySelectorAll("[data-item]");
            itemData = [];

            for (var n = 0; n < items.length; n++) {
                item = items[n];

                itemData.push({
                    "name": item.name.value,
                    "description": item.description.value,
                    "group": groupId,
                    "id": item.dataset.item,
                    "path": item.dataset.path
                });
            }

            groupData = {
                "name": group.querySelector("[data-group-field='title']").value,
                "date": group.querySelector("[data-group-field='date']").value,
                "id": +groupId,
                "order": i,
                "blocks": itemData
            };

            data.push(groupData);
        }

        this.formElement.value = JSON.stringify(data);

    },  

    handleEvent: function(e) {
        switch (e.type) {
            case "click":
                if (e.target === this.addGroupBtn) {
                    this.handleAddGroup(e);
                }
                
                if (e.target.dataset.deleteGroup) {
                    this.handleDeleteGroup(e);
                }

                break;
            case "submit":
                this.serialize();
                return true; 
        }
    },

    render: function(json) {
        var container = this.container;

        for (var i = 0; i< json.length; i++) {
            var item = json[i];
            var div = this.renderItem(item);
            container.appendChild(div.firstElementChild);
        }

        this.bindSortables();
    },

    renderItem: function(item) {
        var div = document.createElement('div');
        div.innerHTML = nunjucks.render("group.j2", item);
        this._divs.push(div);
        return div;
    },

    bindSortables: function() {
        var sortables = [];
        var selector = "[data-items]";
        var className = "group--empty";

        $(selector).sortable({
            items: "[data-item]",
            axis: "y" ,
            opacity: .5,
            connectWith: $(selector),
            // placeholder: 'group--empty'
            // revert: true
            out: function(event, ui) {
                var sender = ui.sender[0];

                if (!sender.firstElementChild) {
                    sender.classList.add(className);
                }
            },

            receive: function(event, ui) {
                var item = ui.item[0];
                var parent = item.parentNode;

                item.parentNode.classList.remove(className);
            }
        });

        $(this.containerSelector).sortable({
            items: this.groupSelector,
            axis: "y" ,
            opacity: .5,
            // revert: true
        });
    },

    unbindSortables: function() {
        $("[data-items]").sortable("destroy");
        $(this.containerSelector).sortable("destroy");
    },

    addGroup: function() {
        var container = this.container;
        this.unbindSortables();
        var newGroupData = jQuery.extend(true, {}, this.groupTemplate);
        newGroupData.id = this._divs.length;

        var newGroupEl = this.renderItem(newGroupData);
        container.insertBefore(newGroupEl.firstElementChild, container.firstChild);

        this.bindSortables();
    },

    handleAddGroup: function(e) {
        this.addGroup();
    },

    handleDeleteGroup: function(e) {
        var $parentEl = $(e.target).parents(".field__group");
        var parentEl, items, defaultGroup, defaultGroupItemContainer, fragment;

        if ($parentEl.length) {
            parentEl = $parentEl[0];
            
            items = parentEl.querySelectorAll('[data-item]');
            
            if (items.length) {

                fragment = document.createDocumentFragment();

                for (var i = 0; i < items.length; i++) {
                    fragment.appendChild(items[i]);
                }

                // Append the items to the default group
                defaultGroup = document.querySelector('[data-sortable-group="0"]');
                
            }

            trueParent = parentEl;
            trueParent.classList.add("group--removed");
            
            setTimeout(function() {
                if (defaultGroup) {
                    defaultGroupItemContainer = defaultGroup.querySelector("[data-items]");
                    defaultGroupItemContainer.insertBefore(fragment, defaultGroupItemContainer.firstChild);
                }
                trueParent.parentElement.removeChild(trueParent);
            }, 220);            
        }
    }
};