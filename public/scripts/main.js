const menuIcon = document.querySelector(".display-mobile.fa-bars");
const navList = document.querySelector("nav > div.container > ul");

menuIcon.addEventListener("click", () => {
  if (navList.style.display === "block") {
    navList.style.display = "none";
  } else {
    navList.style.display = "block";
  }
});