var pfx = ["webkit", "moz", "MS", "o", ""];
function PrefixedEvent(element, type, callback) {
    for (var p = 0; p < pfx.length; p++) {
        if (!pfx[p]) type = type.toLowerCase();
        element.addEventListener(pfx[p]+type, callback, false);
    }
}


var Group = function(id, data) {
    this._id = id;
    this.order = 0;
    if (!data) {
        data = jQuery.extend(true, {}, this.defaults);
    }
    return this.render(data);
};

Group.prototype = {
    defaults: {
        "name": "Work block title...",
        "description": "Work block description...",
        "order": 0,
        "date": +new Date(),
        "id": 0
    },
    render: function(data) {
        data.id = this._id;
        var div = document.createElement('div');
        div.innerHTML = nunjucks.render("group.j2", data);
        this.el = div.firstElementChild;
        this.data = data;

        this.titleEl = this.el.querySelector("[data-group-field='title']");
        this.dateEl = this.el.querySelector("[data-group-field='date']");
        this.sortDown = this.el.querySelector("[data-sort-down]");
        this.sortUp = this.el.querySelector("[data-sort-up]");

        this.itemContainer = this.el.querySelector("[data-items]");
        return this;
    },
    getEl: function() {
        return this.el;
    },
    setOrder: function(num) {
        this.order = +num;

        var toggleClass = "control--hide";

        if (!this.sortUp) {
            return;
        }

        if (this.order === 0) {
            this.sortUp.classList.add(toggleClass);
        } else {
            if (this.sortUp.classList.contains(toggleClass)) {
                this.sortUp.classList.remove(toggleClass);
            }
        }

        if (this.isLast) {
            this.sortDown.classList.add(toggleClass);
        } else {
            if (this.sortDown.classList.contains(toggleClass)) {
                this.sortDown.classList.remove(toggleClass);
            }
        }
    },
    setLast: function(bool) {
        this.isLast = bool;
    },
    getData: function() {
        return this.data;
    },
    getId: function() {
        return this._id;
    },
    saveData: function() {
        var title = false;
        var date = false;

        if (this.titleEl) {
            title = this.titleEl.value;
        }

        if (this.dateEl) {
            date = this.dateEl.value;
        }

        return {
            "name": title,
            "date": date,
            "path": this.el.dataset.path
//            "order": this.data.order,
//            "blocks": []
        }
    },

    addChild: function(data) {
        var el = document.createElement("div");
        el.innerHTML = nunjucks.render("sequence.j2", {item: data});
        this.itemContainer.appendChild(el.firstElementChild);
        this.itemContainer.classList.remove("group--empty");
    }
};




module.exports =  {
    originalFormElementId: "form_groups",
    containerSelector: '[data-group-container]',
    groupSelector: "[data-sortable-group]",
    addSelector: "[data-add-group]",
    deleteSelector: "[data-delete-group]",
    formSelector: "form[name='form']",
    upSelector: "[data-sort-up]",
    downSelector: "[data-sort-down]",
    sortSelector: "[data-sort]",
    submitSelector: "[data-submit]",
    _divs: [],
    _sortables: [],
    _groups: [],
	init: function() {
        this.formEl = document.querySelector(this.formSelector);
        // console.log(this.formEl);
        this.formElement = document.getElementById(this.originalFormElementId);
		var json = JSON.parse(this.formElement.value);


        this.addGroupBtn = document.querySelector(this.addSelector);
        this.deleteGroupBtn = document.querySelectorAll(this.deleteSelector);
        this.container = document.querySelector(this.containerSelector);
        this.submitButton = document.querySelector(this.submitSelector);
        this.addGroupBtn.addEventListener("click", this, false);
        this.container.addEventListener("click", this, false);
        this.formEl.addEventListener("submit", this, false);
        this.submitButton.addEventListener("click", this, false);

        this.render(json);

	},

    handleEvent: function(e) {
        switch (e.type) {
            case "click":
                if (e.target === this.addGroupBtn) {
                    return this.handleAddGroup(e);
                }
                
                if (typeof(e.target.dataset.deleteGroup) !== "undefined") {
                    return this.handleDeleteGroup(e);
                }

                if (typeof(e.target.dataset.sortDown) !== "undefined") {
                    return this.moveGroup("down", e.target.dataset.sortDown);
                }

                if (typeof(e.target.dataset.sortUp) !== "undefined") {
                    return this.moveGroup("up", e.target.dataset.sortUp);
                }

                if (typeof(e.target.dataset.submit) !== "undefined") {
                    this.serialize();
                    return this.formEl.submit();
                }

                break;

        }
    },

    render: function(json) {
        var container = this.container;
        var groups = this._groups;
        var data, div, group;

        var ids = [];


        defaultGroup = new Group(0, {
            "name": "default",
            "data": false
        });
        groups.push(defaultGroup);

        ids.push(0);

        for (var i = 1; i < json.length; i++) {
            data = json[i-1];
            if (data.type == "Springload\\WorkBlock") {
                group = new Group(i, data);
                groups.push(group);
                container.appendChild(group.getEl());
                ids.push(i);
            }
            if (data.type == "Springload\\Sequence") {
                defaultGroup.addChild(data);
            }
        }

        container.appendChild(defaultGroup.getEl());

        // IDs may not come back in a logical order. Sort 'em out.
        this.setNextId(Math.max.apply(null, ids) + 1);
        this.updateGroupOrders();
        this.bindSortables();
    },

    setNextId: function(id) {
        this.nextId = id;
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

//        $(this.containerSelector).sortable({
//            items: this.groupSelector,
//            axis: "y" ,
//            opacity: .5,
//            // revert: true
//        });
    },

    unbindSortables: function() {
        $("[data-items]").sortable("destroy");
//        $(this.containerSelector).sortable("destroy");
    },

    handleAddGroup: function(e) {
        var container = this.container;
        this.unbindSortables();

        var groupId = this.nextId;
        var group = new Group(groupId);
        container.insertBefore(group.getEl(), container.firstChild);

        this.bindSortables();

        this.setNextId(groupId + 1);

        this._groups.push(group);
        this.updateGroupOrders();
    },

    getGroup: function(num) {
        return this._groups[num];
    },

    getGroupById: function(num) {
        var i, group, groups, len;
        groups = this._groups;
        len = groups.length;
        num = +num;

        for (i = 0; i < len; i++) {
            group = groups[i];

            if (group.getId() === num) {
                return group;
            }
        }
    },

    removeGroupById: function(id) {
        var i, group, groups, len;
        groups = this._groups;
        len = groups.length;


    },

    handleDeleteGroup: function(e) {
        var parentGroup, i, parentEl, items, defaultGroup, defaultGroupItemContainer, fragment;

        var groupId = +(e.target.dataset.deleteGroup);
        parentGroup = this.getGroupById(groupId);

        if (!parentGroup) {
            return;
        }

        // Append the items to the default group
        defaultGroup = this.getGroupById(0);

        parentEl = parentGroup.getEl();
        items = parentEl.querySelectorAll('[data-item]');

        if (items.length) {

            fragment = document.createDocumentFragment();

            for (i = 0; i < items.length; i++) {
                fragment.appendChild(items[i]);
            }
        }

        parentEl.classList.add("group--removed");

        // Allow some time for a pretty animation

        setTimeout(function() {
            if (fragment) {
                defaultGroupItemContainer = defaultGroup.itemContainer;
                defaultGroupItemContainer.insertBefore(fragment, defaultGroupItemContainer.firstElementChild);
            }
            parentEl.parentElement.removeChild(parentEl);
            this.updateGroupOrders();
            this.removeGroupById(groupId);
        }.bind(this), 220);
    },




    moveGroup: function(direction, id) {
        var parentGroup = this.getGroupById(id);

        if (!parentGroup) {
            return;
        }

        var parent = parentGroup.getEl();
        var targetNode;

        if (direction === "up") {
            targetNode = parent.previousElementSibling;
            if (!targetNode) {
                return;
            }
        } else if (direction === "down") {
            targetNode = parent.nextElementSibling;
            if (targetNode !== null) {
                targetNode = targetNode.nextElementSibling;
            }
        }


        parent.parentElement.insertBefore(parent, targetNode);

        this.updateGroupOrders();

    },


    updateGroupOrders: function() {
        var siblings, item, sibling, i;
        siblings = this.container.querySelectorAll(this.groupSelector);

        for (i = 0; i < siblings.length; i++) {
            sibling = siblings[i];
            item = this.getGroupById(+(sibling.dataset.sortableGroup));
            if (!item) {
                continue;
            }

            if (i === siblings.length-1) {
                item.setLast(true);
            } else {
                item.setLast(false);
            }

            item.setOrder(i);
        }
    },

    serialize: function() {
        var groups = this._groups;
        var group, items, item, itemData, groupData, data, len;

        data = [];
        len = groups.length;

        for (var i = 0; i < len; i++) {
            group = groups[i];

            items = group.el.querySelectorAll("[data-item]");
            itemData = [];

            for (var n = 0; n < items.length; n++) {
                item = items[n];

                var elements = item.elements;

                itemData.push({
                    "name": elements.namedItem("name").value,
                    "description": elements.namedItem("description").value,
                    "id": item.dataset.item,
                    "path": item.dataset.path
                });
            }

            groupData = group.saveData();
            groupData["children"] = itemData;
            data.push(groupData);
        }

        console.log(JSON.stringify(data));

        this.formElement.value = JSON.stringify(data);

    }
};