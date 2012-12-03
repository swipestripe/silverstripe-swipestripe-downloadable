<table class="table table-bordered order-downloads">
  <thead>     
    <tr>
      <th>Download</th>
      <th>Quantity</th>
      <th>Download Limit</th>
      <th>Download Link</th>
    </tr>
  </thead>
  <tbody>
    <% control Downloads %>  
      <tr>
        <% control Object %> 
        <td>$Title</td>
        <% end_control %>
        
        <td>$Quantity</td>
        <td>$DownloadLimit ($RemainingDownloadLimit downloads remaining)</td>
        <td>
          <% if DownloadLink %>
            <a href="$DownloadLink" target="_blank">Download</a>
            downloaded $DownloadCount time(s)
          <% else %>
          
            <% if RemainingDownloadLimit = 0 %>
              There are no downloads remaining, you have<br /> reached your limit.
            <% else %>
              Download link will appear when payment is complete.
            <% end_if %>
            
          <% end_if %>
        </td>
      </tr>
    <% end_control %>
  </tbody>
</table>

<table class="table table-bordered">
  <thead>     
    <tr>
      <th>Product</th>
      <th>License Key</th>
    </tr>
  </thead>
  <tbody>
    <% control LicenseKeys %>  
      <tr>
        <% control Item.Product %> 
        <td>$Title</td>
        <% end_control %>
        
        <td><% if Order.Paid %>$LicenseKey<% else %>License key will appear when payment is complete.<% end_if %></td>
      </tr>
    <% end_control %>
  </tbody>
</table>
