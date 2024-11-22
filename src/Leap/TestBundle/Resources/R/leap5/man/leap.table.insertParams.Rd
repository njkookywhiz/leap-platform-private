\name{leap.table.insertParams}
\alias{leap.table.insertParams}
\title{
    Table helper function.
}
\description{
    Fills SQL code with given parameters.
}
\usage{
    leap.table.insertParams(sql, params, connection)
}
\arguments{
    \item{sql}{
        SQL code.
    }
    \item{params}{
        Named list with parameters.
    }
    \item{connection}{
        Connection to use.
    }
}
\details{
}
\value{
    SQL code with inserted parameters.
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    id <- 123
    sql <- "SELECT * FROM db.table WHERE id={{id_value}}"
    sql <- leap.table.insertParams(sql,list(id_value=id))
}

\keyword{table}
\keyword{database}
