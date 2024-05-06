@extends('email.layouts.master')
@section('content')
<tr style="display: flex;">
  <td style="margin-top: 56px;">
    <div style="font-weight: 300; font-size:16px; ">
      Hi {{ucfirst($to_name)}},
      <br> Congratulations.

      @if(!empty($resend))
        <br> <span>You requested an email verification link.</span>
      @endif

      @if(!empty($project_id))
        <br> <br> 
        <span>
          You just created a contract on {{config("company.name")}}. To access your contract on {{config("company.name")}}, copy the code below or click <a href="{{$host}}/preview-contract/{{$project_id}}">here.</a>
        </span>     
        <br>
        <b style="padding: 10px;margin: 10px 0;display: block;background-color: #ce00b54d;color: black;border-radius: 3px;text-align: center;">
            {{$project_id}}
        </b>
      @endif

      @if(!empty($contract_id))
        <br> <br> 
         <span>
          You have a contract agreement from {{$from_name}} waiting for you on {{config("company.name")}}.
          <br>
          To access the contract on {{config("company.name")}}, copy the code below or click <a href="{{$host}}/preview-contract/{{$contract_id}}">here.</a>
        </span>
        <br>
        <b style="padding: 10px;margin: 10px 0;display: block;background-color: #ce00b54d;color: black;border-radius: 3px;text-align: center;">
            {{$contract_id}}
        </b>
      @endif

      <span>
        <br /> <br />
        @if(!empty($project_id) || !empty($contract_id))
          But first you need
        @else
          The first step is 
        @endif
       to verify your email so as to have access to preview your contract.
      </span>
    </div>
  </td>
</tr>
<tr style="display: flex;">
  <td style="margin-top:20px">
    <table>
      <tbody>
        <tr>
          <td width="250px" style="margin-top: 20px;">
            <table bgcolor="#673ab7" style="border-radius:8px;text-decoration:none;width:100%;border:solid 2px #673ab7" cellpadding="0" cellspacing="0" width="100%">
              <tbody>
                <tr>
                  <td class="m_7030289048136942514button-copy" style="font-family:NetflixSans-Bold,Helvetica,Roboto,Segoe UI,sans-serif;font-weight:500;font-size:18px;letter-spacing:-0.2px;color:#f5f5f1;" align="center" height="42">
                    <a href="{{$link}}" style="text-align:center;display:inline-block;text-decoration:none;color:#f5f5f1" target="_blank">Verify Email</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </tbody>
    </table>
  </td>
</tr>
@endsection