<table class="table table-bordered order-downloads">
	<thead>     
		<tr>
			<th>File</th>
			<th>Licence Key</th>
			<th>Download Limit</th>
			<th>Download Link</th>
		</tr>
	</thead>
	<tbody>
		<% loop Downloads %>  
			<tr>
				<td>$Title</td>
				<td>$LicenceKey</td>
				<td>$DownloadLimit <br />($RemainingDownloadLimit downloads remaining)</td>

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
		<% end_loop %>
	</tbody>
</table>
