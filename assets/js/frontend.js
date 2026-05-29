const toggleButton = document.getElementsByClassName("wppr-previous-button")[0];
const historyBox = document.getElementsByClassName("wppr-history-box")[0];

if (!!toggleButton) {
    toggleButton.addEventListener("click", () => {
        const style = getComputedStyle(historyBox);
        historyBox.style.display = style.display === "none" ? "block" : "none";
    });
}
