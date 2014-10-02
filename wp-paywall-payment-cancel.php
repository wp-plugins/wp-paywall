<?php
echo '<script>if (window.opener){ window.close();} else if (top.paywall_dg_flow.isOpen() == true){top.paywall_dg_flow.closeFlow();} else if (top.paywall_token_flow.isOpen() == true){top.paywall_token_flow.closeFlow();}</script>';
