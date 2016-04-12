function toggleMore() {
    var parent = this.parentElement;
    if (parent.classList.contains("is-open")) {
        return parent.classList.remove("is-open")
    }
    this.parentElement.classList.add("is-open");
}

(function () {
    var more = document.getElementsByClassName("trigger-more");
    Array.prototype.map.call(more, function (link) {
        link.addEventListener("click", toggleMore);
    });
})();
