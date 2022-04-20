
// Test the js implementation in the plugin

function StringSearch() {
  var SearchTerm = document.getElementById("text_search").value;
  var TextSearch = document.getElementById("text_area").value;

  if (SearchTerm.length > 0 && TextSearch.indexOf(SearchTerm) > -1) {
    alert("String Found. Search Complete");
  } else {
    alert("No Data found in Text Area");
  }
}